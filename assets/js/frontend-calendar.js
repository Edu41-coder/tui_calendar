/**
 * Script principal du calendrier TUI - Version refactorisée en classes ES6
 * Ce fichier contient toute la logique JavaScript pour le calendrier
 */
class CalendarFrontend {
  /**
   * Constructeur qui initialise le calendrier
   * @param {Object} options - Options de configuration
   */
  constructor(options = {}) {
    console.log("CalendarFrontend: Constructeur appelé");
    // Configuration par défaut
    this.config = {
      baseUrl: "/tui_calendar/",
      defaultView: "week",
      ...options
    };
    
    // Variables d'état
    this.calendar = null;
    this.calendarData = window.calendarData || [];
    this.calendarIds = window.calendarIds || [];
    this.attachHandlersTimeout = null;
    this.arrowClickInProgress = false;
    
    // Composants UI
    this.modal = null;
    this.modalTitle = null;
    this.editEventId = null;
    this.deleteEventBtn = null;
    
    // MODIFICATION: Utiliser uniquement l'initialisation forcée
    // Supprimer l'initialisation automatique au chargement du DOM
    // document.addEventListener("DOMContentLoaded", () => this.initialize());
  }
  
  /**
   * Initialise tous les composants du calendrier
   */
  initialize() {
    try {
      console.log("Démarrage de l'initialisation");
      
      // Vérifier TUI Calendar
      console.log("TUI disponible?", typeof tui !== 'undefined');
      console.log("TUI Calendar disponible?", typeof tui !== 'undefined' && typeof tui.Calendar !== 'undefined');
      
      if (typeof tui === 'undefined' || typeof tui.Calendar === 'undefined') {
        console.error("ERREUR CRITIQUE: La bibliothèque TUI Calendar n'est pas chargée");
        alert("Erreur: La bibliothèque TUI Calendar n'est pas chargée correctement");
        return;
      }
      
      console.log("DOM prêt?", document.readyState);
      console.log("Document complet?", document.body !== null);
      
      // Vérification du backend
      console.log("Backend créé?", window.calendarBackend instanceof window.CalendarBackendClass);
      console.log("Contenu de window.calendarBackend:", window.calendarBackend);
      
      console.log("CalendarFrontend: Initialisation...");
      console.log("Backend disponible?", !!window.calendarBackend);
      this.backend = window.calendarBackend;
      
      // Ajouter les styles pour les flèches d'extension
      this.addExtensionArrowStyles();
      
      // Initialiser le calendrier
      this.calendar = this.initializeCalendar(this.calendarData);
      
      // Configuration des événements et de l'UI
      this.attachEventHandlers();
      this.updateCalendarHeader();
      this.updateViewButtons(this.config.defaultView);
      this.initializeDatepicker();
      this.initializeModals();
      this.initializeCalendarCheckboxes();
      
      // Charger les événements initiaux
      this.loadInitialEvents();
      
      // Nettoyage automatique des sélections
      this.setupSelectionCleanup();
      
      // Surveiller les rendus pour recharger les événements
      this.calendar.on('afterRender', () => this.reloadEvents());
    } catch (error) {
      console.error("ERREUR CRITIQUE LORS DE L'INITIALISATION:", error);
      alert("Le calendrier n'a pas pu être initialisé: " + error.message);
    }
  }
  setupSelectionCleanup() {
    // Nettoyage de la sélection en vue mensuelle
    document.addEventListener("click", (e) => {
      if (!this.calendar) return;
      
      if (this.calendar.getViewName() === "month") {
        // Si le clic n'est pas sur une sélection ou un popup
        const isSelection = e.target.closest(
          ".tui-full-calendar-month-guide-block, .tui-full-calendar-month-creation-guide"
        );
        const isPopup = e.target.closest(".tui-full-calendar-popup-container");
        
        if (!isSelection && !isPopup) {
          // Supprime toutes les sélections du mois
          document.querySelectorAll(
            ".tui-full-calendar-month-guide-block, .tui-full-calendar-month-creation-guide"
          ).forEach(el => {
            el.parentNode && el.parentNode.removeChild(el);
          });
        }
      }
    }, true);
    
    // Nettoyage de la sélection en vue semaine
    document.addEventListener("click", (e) => {
      if (!this.calendar) return;
      
      if (this.calendar.getViewName() === "week") {
        // Si le clic n'est pas sur une sélection ou un popup
        const isSelection = e.target.closest(".tui-full-calendar-daygrid-guide-creation-block");
        const isPopup = e.target.closest(".tui-full-calendar-popup-container");
        
        if (!isSelection && !isPopup) {
          // Supprime toutes les sélections de la semaine
          document.querySelectorAll(".tui-full-calendar-daygrid-guide-creation-block")
            .forEach(el => {
              el.parentNode && el.parentNode.removeChild(el);
            });
        }
      }
    }, true);
  }

  /**
   * Ajoute les styles CSS pour les flèches d'extension
   */
  addExtensionArrowStyles() {
    const styleElement = document.createElement("style");
    styleElement.textContent = `
      .event-extension-arrow {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 30px;
        height: 30px;
        background-color: rgba(255, 255, 255, 0.9);
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        z-index: 1000;
        opacity: 0;
        transition: opacity 0.2s, transform 0.2s;
      }
      
      .event-extension-arrow.left {
        left: -15px;
      }
      
      .event-extension-arrow.right {
        right: -15px;
      }
      
      .tui-full-calendar-time-schedule:hover .event-extension-arrow,
      .event-content:hover .event-extension-arrow {
        opacity: 1;
      }
      
      .event-extension-arrow:hover {
        transform: translateY(-50%) scale(1.2);
        opacity: 1;
      }
    `;
    document.head.appendChild(styleElement);
  }
  
  /**
   * Initialise l'instance TUI Calendar
   */
  initializeCalendar(calendarData) {
    const calendarEl = document.getElementById("calendar");
    console.log("Élément #calendar trouvé?", calendarEl !== null);
    console.log("Élément #calendar:", calendarEl);
    
    if (!calendarEl) {
      console.error("ERREUR CRITIQUE: Élément #calendar non trouvé dans le DOM");
      alert("Erreur: Le conteneur du calendrier n'a pas été trouvé.");
      return null;
    }

    return new tui.Calendar("#calendar", {
      defaultView: this.config.defaultView,
      taskView: false,
      scheduleView: ["time", "allday"],
      useCreationPopup: false,
      useDetailPopup: false,
      calendars: calendarData,
      week: {
        hourStart: 0,
        hourEnd: 24,
        startDayOfWeek: 1,
        daynames: ["Dim", "Lun", "Mar", "Mer", "Jeu", "Ven", "Sam"],
        narrowWeekend: false,
        showTimezoneCollapseButton: false,
        timezonesCollapsed: false,
        currentTimeIndicator: false,
      },
      month: {
        startDayOfWeek: 1,
        daynames: ["Dim", "Lun", "Mar", "Mer", "Jeu", "Ven", "Sam"],
      },
      template: {
        allday: schedule => schedule.title,
        alldayTitle: () => '<div style="text-align: center; width: 100%;">Toute la journée</div>',
        time: schedule => this.renderTimeTemplate(schedule),
        monthGridSchedule: schedule => this.renderMonthTemplate(schedule)
      },
    });
  }
  
  /**
   * Template de rendu pour les événements dans la vue time
   */
  renderTimeTemplate(schedule) {
    const currentView = this.calendar.getViewName();

    // Afficher les flèches uniquement en vue "week" et pour les événements "time"
    if (currentView !== "week" || schedule.category !== "time") {
      // Affichage normal sans flèches
      return `
        <div class="event-content" data-schedule-id="${schedule.id}" data-calendar-id="${schedule.calendarId}" style="
          position: relative;
          width: 100%;
          height: 100%;
          box-sizing: border-box;
          border: 8px solid ${schedule.raw?.calendarColor || "#333"};
          background-color: ${schedule.raw?.categoryColor || "#999"};
          color: ${schedule.raw?.categoryTextColor || "#000000"};
        ">
          <div style="padding: 2px 8px;">
            ${schedule.title}
          </div>
        </div>
      `;
    }

    // Affichage avec flèches pour la vue semaine et les événements "time"
    const calColor = schedule.raw?.calendarColor || "#333";
    const catColor = schedule.raw?.categoryColor || "#999";
    const textColor = schedule.raw?.categoryTextColor || "#000000";
    const scheduleId = schedule.id;
    const calendarId = schedule.calendarId;
    const scheduleDataJson = encodeURIComponent(JSON.stringify(schedule));
    const padding = "2px 8px";

    return `
      <div class="event-content" data-schedule-id="${scheduleId}" data-calendar-id="${calendarId}" style="
        position: relative;
        width: 100%;
        height: 100%;
        box-sizing: border-box;
        border: 8px solid ${calColor};
        background-color: ${catColor};
        color: ${textColor};
      ">
        <div style="padding: ${padding};">
          ${schedule.title}
        </div>
        <div class="event-extension-arrow left"
          data-schedule-id="${scheduleId}"
          data-calendar-id="${calendarId}"
          data-schedule-data="${scheduleDataJson}">
          <i class="fas fa-chevron-left"></i>
        </div>
        <div class="event-extension-arrow right"
          data-schedule-id="${scheduleId}"
          data-calendar-id="${calendarId}"
          data-schedule-data="${scheduleDataJson}">
          <i class="fas fa-chevron-right"></i>
        </div>
      </div>
    `;
  }
  
  /**
   * Template de rendu pour les événements dans la vue month
   */
  renderMonthTemplate(schedule) {
    const calColor = schedule.raw?.calendarColor || "#333";
    const catColor = schedule.raw?.categoryColor || "#999";
    const textColor = schedule.raw?.categoryTextColor || "#000000";

    return `
      <div class="event-content month-view" data-schedule-id="${schedule.id}" data-calendar-id="${schedule.calendarId}" style="
        position: relative;
        width: 100%;
        height: 100%;
        box-sizing: border-box;
        border-left: 4px solid ${calColor};
        background-color: ${catColor};
        color: ${textColor};
      ">
        <div style="padding: 0px 2px;">
          ${schedule.title}
        </div>
      </div>
    `;
  }
  
  /**
   * Recharge les événements pour la période affichée
   */
  reloadEvents() {
    const currentView = this.calendar.getViewName();
    
    // Obtenir la plage de dates affichée réellement
    const rangeStart = this.calendar.getDateRangeStart();
    const rangeEnd = this.calendar.getDateRangeEnd();
    
    // Convertir et corriger le décalage
    let startDate = rangeStart instanceof Date ? rangeStart : 
                   (rangeStart._date ? new Date(rangeStart._date) : new Date(rangeStart));
    
    let endDate = rangeEnd instanceof Date ? rangeEnd : 
                 (rangeEnd._date ? new Date(rangeEnd._date) : new Date(rangeEnd));
                 
    // IMPORTANT: Ajouter exactement 1 jour à la date de fin pour inclure tous les événements
    endDate = new Date(endDate);
    endDate.setDate(endDate.getDate() + 1);
    
    // Récupérer les IDs des calendriers visibles
    const visibleCalendars = this.getVisibleCalendarIds();
    
    console.log(`Rechargement des événements pour la vue ${currentView} du ${startDate.toLocaleDateString()} au ${endDate.toLocaleDateString()}`);
    console.log(`Filtré par calendriers: ${visibleCalendars.join(', ')}`);
    
    // Utiliser une approche unifiée pour toutes les vues
    this.backend.loadEvents(startDate, endDate, (error, events) => {
      if (error) {
        console.error("Erreur lors du chargement des événements:", error);
        // Afficher une alerte visible à l'utilisateur
        alert("Erreur de chargement des événements: " + error.message);
        return;
      }
      
      console.log(`${events.length} événements chargés pour la vue ${currentView}`);
      console.log("Détail des événements:", JSON.stringify(events));
      
      // Filtrer les événements par calendrier visible
      if (events && events.length) {
        events = events.filter(event => visibleCalendars.includes(event.calendarId.toString()));
        console.log(`Après filtrage: ${events.length} événements à afficher`);
      }
      
      // Vider le calendrier
      this.calendar.clear();
      
      if (!events || !events.length) return;
      
      // Créer tous les événements et forcer un rendu complet
      this.calendar.createSchedules(events.map(event => this.transformEvent(event)));
      this.calendar.render(); // AJOUT CRUCIAL: Force le rendu
    }, false); // Plus besoin de traitement spécial pour la vue jour
  }
  
  /**
   * Transforme un événement du format backend au format TUI Calendar
   */
  transformEvent(event) {
    try {
      // S'assurer que les dates sont valides
      const startDate = new Date(event.start);
      const endDate = new Date(event.end);
      
      console.log(`Transformation événement ${event.id}: ${startDate} à ${endDate}`);
      
      const isAllDay = event.isAllDay === true || event.isAllDay === 1 || event.isAllDay === "1";
      
      return {
        id: event.id,
        calendarId: event.calendarId,
        title: event.title,
        start: startDate,
        end: endDate,
        isAllDay: isAllDay,
        category: isAllDay ? "allday" : "time",
        raw: {
          calendarColor: event.calendarColor || "#333333",
          categoryColor: event.categoryColor || "#999999",
          categoryTextColor: event.categoryTextColor || "#000000",
          categoryId: event.categoryId,
          location: event.location || "",
          body: event.body || ""
        }
      };
    } catch (error) {
      console.error("Erreur de transformation d'événement:", error, event);
      return null;
    }
  }
  
  /**
   * Charge les événements initiaux depuis le backend
   */
  loadInitialEvents() {
    // Calculer les dates de début et de fin pour le mois en cours
    const currentDate = this.calendar.getDate();
    const start = new Date(
      currentDate.getFullYear(),
      currentDate.getMonth() - 1,
      1
    );
    const end = new Date(
      currentDate.getFullYear(),
      currentDate.getMonth() + 2,
      0
    );

    this.backend.loadEvents(start, end, (error, events) => {
      if (error) {
        console.error("Erreur lors du chargement des événements:", error);
        return;
      }

      if (events && events.length) {
        // Convertir les événements au format TUI Calendar et les ajouter
        this.calendar.createSchedules(events.map(event => this.transformEvent(event)));
        this.calendar.render();
      }
    });
  }
  
  /**
   * Filtre les calendriers visibles actuellement
   * @returns {Array} Liste des IDs de calendriers visibles
   */
  getVisibleCalendarIds() {
    const visibleCalendars = [];
    document.querySelectorAll('.calendar-checkbox:checked').forEach(checkbox => {
      visibleCalendars.push(checkbox.value);
    });
    console.log("Calendriers visibles:", visibleCalendars);
    return visibleCalendars;
  }

  /**
   * Attache tous les gestionnaires d'événements
   */
  attachEventHandlers() {
    // Clic sur les flèches d'extension
    document.addEventListener("click", (e) => {
      const arrow = e.target.closest(".event-extension-arrow");
      if (arrow) {
        // Définir un flag global avec une plus longue durée
        this.arrowClickInProgress = true;
        setTimeout(() => {
          this.arrowClickInProgress = false;
        }, 300);

        // S'assurer que l'événement ne se propage pas
        e.stopPropagation();
        e.stopImmediatePropagation();
        e.preventDefault();

        const isLeft = arrow.classList.contains("left");
        const cachedData = arrow.getAttribute("data-schedule-data");

        if (cachedData) {
          try {
            const scheduleData = JSON.parse(decodeURIComponent(cachedData));
            this.handleArrowClick(isLeft ? "left" : "right", scheduleData);
          } catch (error) {
            console.error("Erreur de parsing des données:", error);
          }
        }

        return false;
      }
    }, true);

    // Clic droit sur les événements (menu contextuel)
    document.addEventListener("contextmenu", (e) => {
      const targetElement = e.target;
      const eventElement =
        targetElement.closest(".tui-full-calendar-time-schedule") ||
        targetElement.closest(".event-content") ||
        targetElement.closest(".tui-full-calendar-weekday-schedule");

      if (eventElement) {
        e.preventDefault();
        e.stopPropagation();

        const scheduleId = eventElement.getAttribute("data-schedule-id");
        if (!scheduleId) {
          console.warn("Clic droit sur événement sans ID");
          return false;
        }

        console.log("Clic droit sur événement:", scheduleId);

        // SOLUTION : Recherche améliorée de l'événement, similaire au double-clic
        let foundEvent = null;

        // 1. D'abord, essayer de trouver dans les calendriers spécifiques
        for (const calId of this.calendarIds) {
          try {
            const schedule = this.calendar.getSchedule(
              scheduleId,
              calId.toString()
            );
            if (schedule) {
              foundEvent = schedule;
              break;
            }
          } catch (err) {
            // Ignorer les erreurs et continuer la recherche
          }
        }

        // 2. Si rien n'est trouvé, chercher dans tous les événements affichés
        if (!foundEvent) {
          try {
            // Pour les nouveaux événements, leur ID peut être dans l'élément DOM
            const calendarId = eventElement.getAttribute("data-calendar-id");
            if (calendarId) {
              const schedule = this.calendar.getSchedule(scheduleId, calendarId);
              if (schedule) {
                foundEvent = schedule;
              }
            }
          } catch (err) {
            // Ignorer les erreurs
          }
        }

        // 3. En dernier recours, extraire les données des attributs HTML
        if (!foundEvent && eventElement) {
          // Récupérer les informations de style pour les couleurs
          const style = window.getComputedStyle(eventElement);
          const titleElement = eventElement.querySelector("div");

          // Créer un événement synthétique à partir des données DOM
          foundEvent = {
            id: scheduleId,
            calendarId:
              eventElement.getAttribute("data-calendar-id") ||
              document.getElementById("eventCalendar").value,
            title: titleElement ? titleElement.innerText : "Sans titre",
            // Récupérer les dates depuis le serveur ou utiliser une approximation
            start: new Date(),
            end: new Date(new Date().getTime() + 3600000), // +1 heure par défaut
            raw: {
              // Utiliser les couleurs calculées
              calendarColor: style.borderColor || "#333",
              categoryColor: style.backgroundColor || "#fff",
              categoryTextColor: style.color || "#000",
              categoryId:
                document.getElementById("eventCategory").value || "1",
            },
          };

          // Essayer de récupérer les données complètes via une requête AJAX
          this.backend.getEvent(
            scheduleId,
            foundEvent.calendarId,
            (error, eventData) => {
              if (!error && eventData) {
                // Si on a réussi à récupérer les données, ouvrir le modal avec ces données
                this.openCloneModal({
                  title: eventData.title,
                  start: new Date(eventData.start),
                  end: new Date(eventData.end),
                  calendarId: eventData.calendarId,
                  categoryId: eventData.raw?.categoryId,
                  raw: eventData.raw || {},
                });
              }
            }
          );
        }

        if (foundEvent) {
          this.openCloneModal({
            title: foundEvent.title,
            start:
              foundEvent.start instanceof Date
                ? foundEvent.start
                : foundEvent.start && foundEvent.start._date
                ? foundEvent.start._date
                : new Date(foundEvent.start),
            end:
              foundEvent.end instanceof Date
                ? foundEvent.end
                : foundEvent.end && foundEvent.end._date
                ? foundEvent.end._date
                : new Date(foundEvent.end),
            calendarId: foundEvent.calendarId,
            categoryId: foundEvent.raw?.categoryId,
            raw: foundEvent.raw || {},
          });
        } else {
          console.warn(
            "Événement non trouvé pour le clic droit:",
            scheduleId
          );
        }

        return false;
      }
    }, true);

    // Navigation entre les vues
    document.getElementById("day-view").addEventListener("click", () => {
      this.changeView("day");
    });

    document.getElementById("week-view").addEventListener("click", () => {
      this.changeView("week");
    });

    document.getElementById("month-view").addEventListener("click", () => {
      this.changeView("month");
    });

    // Boutons de navigation
    document.getElementById("prev-btn").addEventListener("click", () => {
      this.calendar.prev();
      this.updateCalendarHeader();
      this.reloadEvents();
    });

    document.getElementById("next-btn").addEventListener("click", () => {
      this.calendar.next();
      this.updateCalendarHeader();
      this.reloadEvents();
    });

    document.getElementById("today-btn").addEventListener("click", () => {
      this.calendar.today();
      this.updateCalendarHeader();
      this.reloadEvents();
    });

    // Événements du calendrier
    this.calendar.on("clickSchedule", (e) => {
      // Désactiver complètement le comportement par défaut du clic simple
      e.preventDefault && e.preventDefault();
      // Ne rien faire d'autre - l'édition se fera uniquement par double-clic
    });

    this.calendar.on("beforeCreateSchedule", (eventObj) => {
      const startDate = new Date(eventObj.start);
      const endDate = new Date(
        eventObj.end || new Date(startDate.getTime() + 60 * 60 * 1000)
      );
    
      // Détecte si c'est une sélection all day (vue mois ou eventObj.isAllDay)
      const currentView = this.calendar.getViewName();
      const isAllDay = currentView === "month" || eventObj.isAllDay;
    
      this.openCreateModal(startDate, endDate, isAllDay);
    });

    this.calendar.on("beforeUpdateSchedule", (e) => {
      const schedule = e.schedule;
      const changes = e.changes;

      console.log("Événement redimensionné ou déplacé:", schedule);
      console.log("Modifications:", changes);

      if (changes && (changes.start || changes.end)) {
        // Mettre à jour localement
        this.calendar.updateSchedule(schedule.id, schedule.calendarId, changes);

        // Envoyer au backend
        const moveData = {
          id: schedule.id,
          calendarId: schedule.calendarId,
          start: changes.start || schedule.start,
          end: changes.end || schedule.end,
        };

        this.backend.moveEvent(moveData, (error, response) => {
          if (error) {
            console.error("Erreur lors du déplacement:", error);
            return;
          }
          // AJOUTER CES LIGNES 
          setTimeout(() => {
            this.calendar.render();
          }, 100);
        });

        console.log(
          "Nouvel horaire:",
          changes.start
            ? this.formatDateForInput(changes.start)
            : this.formatDateForInput(schedule.start),
          "à",
          changes.end
            ? this.formatDateForInput(changes.end)
            : this.formatDateForInput(schedule.end)
        );
      }
    });

    // Gestionnaire du bouton Enregistrer
    document.getElementById("saveEventBtn").onclick = () => {
      this.saveEvent();
    };

    // Gestionnaire du bouton Supprimer
    document.getElementById("deleteEventBtn").onclick = () => {
      const confirmModal = new bootstrap.Modal(
        document.getElementById("confirmDeleteModal")
      );
      confirmModal.show();
    };

    // Gestionnaire de confirmation de suppression
    document.getElementById("confirmDeleteEventBtn").onclick = () => {
      const eventId = document.getElementById("editEventId").value;
      const calendarId = document.getElementById("eventCalendar").value;

      if (eventId) {
        // D'abord appeler le backend
        this.backend.deleteEvent(eventId, calendarId, (error, response) => {
          if (!error) {                
            this.reloadEvents(); 
          } else {
            console.error("Erreur lors de la suppression de l'événement:", error);
          }

          // Fermer les modals quelle que soit la réponse
          const confirmModal = bootstrap.Modal.getInstance(
            document.getElementById("confirmDeleteModal")
          );
          const mainEventModal = bootstrap.Modal.getInstance(
            document.getElementById("createEventModal")
          );
          confirmModal.hide();
          mainEventModal.hide();
        });
      }
    };

    // Gestionnaire pour le double-clic (édition d'événement)
    document.querySelector("#calendar").addEventListener("dblclick", (e) => {
      // Si l'utilisateur clique sur un événement
      const eventElement =
        e.target.closest(".tui-full-calendar-time-schedule") ||
        e.target.closest(".event-content") ||
        e.target.closest(".tui-full-calendar-weekday-schedule");

      if (!eventElement) return;

      const scheduleId = eventElement.getAttribute("data-schedule-id");
      const calendarId = eventElement.getAttribute("data-calendar-id");

      if (!scheduleId) {
        console.warn("Double-clic sur événement sans ID");
        return;
      }

      console.log("Double-clic sur événement:", scheduleId);
      e.preventDefault();
      e.stopPropagation();

      // Toujours récupérer directement depuis le backend
      this.backend.getEvent(scheduleId, calendarId, (error, eventData) => {
        if (error || !eventData) {
          console.error("Erreur lors de la récupération des données de l'événement pour édition:", error);

          // Fallback - essayer de récupérer depuis le frontend
          let foundEvent = null;
          for (const calId of this.calendarIds) {
            try {
              const schedule = this.calendar.getSchedule(scheduleId, calId.toString());
              if (schedule) {
                foundEvent = schedule;
                break;
              }
            } catch (err) {
              // Ignorer les erreurs et continuer
            }
          }

          if (foundEvent) {
            this.openEditModal({
              id: foundEvent.id,
              title: foundEvent.title,
              start:
                foundEvent.start instanceof Date
                  ? foundEvent.start
                  : foundEvent.start && foundEvent.start._date
                  ? foundEvent.start._date
                  : new Date(foundEvent.start),
              end:
                foundEvent.end instanceof Date
                  ? foundEvent.end
                  : foundEvent.end && foundEvent.end._date
                  ? foundEvent.end._date
                  : new Date(foundEvent.end),
              calendarId: foundEvent.calendarId,
              raw: foundEvent.raw || {},
            });
          } else {
            console.warn("Événement non trouvé pour édition:", scheduleId);
          }

          return;
        }

        // Si on a réussi à récupérer les données depuis le backend, ouvrir le modal directement
        this.openEditModal(eventData);
      });
    }, true);
  }

  /**
   * Change la vue du calendrier
   * @param {String} viewName - Nom de la vue ('day', 'week', 'month')
   */
  changeView(viewName) {
    console.log(`Changement de vue vers: ${viewName}`);
    
    // Forcer un changement de vue explicite
    this.calendar.changeView(viewName);
    
    // Mettre à jour l'interface
    this.updateViewButtons(viewName);
    this.updateCalendarHeader();
    
    // Forcer le rechargement des événements puis un rendu
    this.reloadEvents();
    
    // Forcer un rendu supplémentaire après un court délai
    setTimeout(() => {
      console.log(`Rendu forcé après changement de vue: ${viewName}`);
      this.calendar.render();
    }, 100);
  }
  
  /**
   * Gère les clics sur les flèches d'extension
   */
  handleArrowClick(direction, scheduleData) {
    console.log(`Clic sur flèche ${direction} détecté pour l'événement:`, scheduleData);

    // Vérification des données minimales requises
    if (!scheduleData || !scheduleData.id || !scheduleData.calendarId) {
      console.error("Données insuffisantes pour la duplication par flèche:", scheduleData);
      return;
    }

    const dayOffset = direction === "left" ? -1 : 1;

    // Récupérer l'événement depuis le backend
    this.backend.getEvent(scheduleData.id, scheduleData.calendarId, (error, eventData) => {
      if (error) {
        console.error("Erreur lors de la récupération des données:", error);

        // Fallback: utiliser les données disponibles
        if (scheduleData.start && scheduleData.end) {
          this.duplicateWithLocalData(scheduleData, dayOffset);
        }
        return;
      }

      // Utiliser les données du backend
      this.duplicateWithBackendData(eventData, dayOffset);
    });
  }
  
  /**
   * Dupliquer un événement avec les données du backend
   */
  duplicateWithBackendData(eventData) {
    // Extraire les dates en tant qu'objets Date
    const startDate = new Date(eventData.start);
    const endDate = new Date(eventData.end);

    // Ajouter le décalage de jour
    startDate.setDate(startDate.getDate() + dayOffset);
    endDate.setDate(endDate.getDate() + dayOffset);

    console.log("Création d'une copie avec dates:", startDate, "à", endDate);

    // Créer le nouvel événement
    const newEventData = {
      title: eventData.title,
      calendarId: eventData.calendarId,
      start: startDate,
      end: endDate,
      isAllDay: eventData.isAllDay,
      category: eventData.category,
      categoryId: eventData.raw?.categoryId,
      raw: eventData.raw,
    };

    this.createDuplicateEvent(newEventData);
  }
  
  /**
   * Dupliquer un événement avec les données locales (fallback)
   */
  duplicateWithLocalData(data, dayOffset) {
    // Extraire les dates, en gérant le cas TZDate
    const getDateFromScheduleDate = function(dateValue) {
      if (dateValue && dateValue._date) return new Date(dateValue._date);
      if (dateValue instanceof Date) return new Date(dateValue);
      return new Date(dateValue);
    };

    const startDate = getDateFromScheduleDate(data.start);
    const endDate = getDateFromScheduleDate(data.end);

    // Ajouter le décalage de jour
    startDate.setDate(startDate.getDate() + dayOffset);
    endDate.setDate(endDate.getDate() + dayOffset);

    console.log("Création d'une copie (fallback) avec dates:", startDate, "à", endDate);

    // Créer le nouvel événement
    const newEventData = {
      title: data.title,
      calendarId: data.calendarId,
      start: startDate,
      end: endDate,
      isAllDay: data.isAllDay,
      category: data.category,
      categoryId: data.raw?.categoryId,
      raw: data.raw,
    };

    this.createDuplicateEvent(newEventData);
  }
  
  /**
   * Créer un événement dupliqué
   */
  createDuplicateEvent(eventData) {
    // Envoyer au backend
    this.backend.saveEvent(eventData, (error, response) => {
      if (error) {
        console.error("Erreur lors de la création de la copie:", error);
        return;
      }
       
      this.reloadEvents();
      // AJOUTER CETTE LIGNE
      setTimeout(() => this.calendar.render(), 100);
    });
  }
  
  /**
   * Enregistrer un événement (création, modification ou duplication)
   */
  saveEvent() {
    // Récupérer les valeurs du formulaire
    const title = document.getElementById("eventTitle").value;
    const startInput = document.getElementById("eventStart").value;
    const endInput = document.getElementById("eventEnd").value;
    const calendarId = document.getElementById("eventCalendar").value;
    const categoryId = document.getElementById("eventCategory").value;
    const eventId = document.getElementById("editEventId").value;
    const isAllDay = document.getElementById("eventAllDay").checked ? 1 : 0;

    // Vérifier si c'est un événement dupliqué
    const isDuplicated = document.getElementById("isDuplicatedEvent")?.value === "true";

    // Validation de base
    if (!title) {
      alert("Veuillez entrer un titre pour l'événement");
      return;
    }
    // Créer des objets Date à partir des chaînes
    const start = new Date(startInput);
    const end = new Date(endInput);

    // Si all day, ajuste les heures
    if (isAllDay) {
      start.setHours(0, 0, 0, 0);
      end.setHours(23, 59, 0, 0);
    }    

    // Récupérer les couleurs de la catégorie sélectionnée
    let categoryColor = "#FFFFFF";
    let categoryTextColor = "#000000";
    const categorySelect = document.getElementById("eventCategory");
    for (let i = 0; i < categorySelect.options.length; i++) {
      if (categorySelect.options[i].value === categoryId) {
        categoryColor = categorySelect.options[i].style.backgroundColor || "#FFFFFF";
        categoryTextColor = categorySelect.options[i].style.color || "#000000";
        break;
      }
    }

    // Récupérer la couleur du calendrier
    let calendarColor = "#FFFFFF";
    const calendarSelect = document.getElementById("eventCalendar");
    for (let i = 0; i < calendarSelect.options.length; i++) {
      if (calendarSelect.options[i].value === calendarId) {
        calendarColor = calendarSelect.options[i].style.backgroundColor || "#FFFFFF";
        break;
      }
    }

    // Préparer les données pour le backend
    const eventData = {
      id: eventId || null,
      title: title,
      start: start,
      end: end,
      calendarId: calendarId,
      categoryId: categoryId,
      isDuplicated: isDuplicated,
      isAllDay: isAllDay,
      raw: {
        calendarColor: calendarColor,
        categoryColor: categoryColor,
        categoryTextColor: categoryTextColor,
        categoryId: categoryId,
      },
    };

    // Envoyer au backend
    this.backend.saveEvent(eventData, (error, response) => {
      if (error) {
        console.error("Erreur lors de l'enregistrement:", error);
        return;
      }
         
      // Forcer un rendu complet après sauvegarde
      this.reloadEvents();
      setTimeout(() => this.calendar.render(), 100);
      
      // Réinitialiser le flag de duplication
      if (document.getElementById("isDuplicatedEvent")) {
        document.getElementById("isDuplicatedEvent").value = "false";
      }

      // Fermer le modal
      const modal = bootstrap.Modal.getInstance(
        document.getElementById("createEventModal")
      );
      modal.hide();
    });
  }
  
  /**
   * Fonctions pour les modaux
   */
  initializeModals() {
    this.modal = new bootstrap.Modal(document.getElementById("createEventModal"));
    this.modalTitle = document.getElementById("createEventModalLabel");
    this.editEventId = document.getElementById("editEventId");
    this.deleteEventBtn = document.getElementById("deleteEventBtn");
    
    // Rendre la méthode openCloneModal accessible globalement
    window.openCloneModal = this.openCloneModal.bind(this);
  }

  openCreateModal(start, end, isAllDay = false) {
    this.modalTitle.textContent = "Créer un événement";
    this.deleteEventBtn.classList.add("d-none");
    this.editEventId.value = "";
  
    const calendarSelect = document.getElementById("eventCalendar");
  
    if (calendarSelect.selectedIndex < 0 && calendarSelect.options.length > 0) {
      calendarSelect.selectedIndex = 0;
    }
    document.getElementById("eventAllDay").checked = isAllDay;
  
    // Si all day, ajuste les heures
    if (isAllDay) {
      const startDate = new Date(start);
      const endDate = new Date(end);
      startDate.setHours(0, 0, 0, 0);
      endDate.setHours(23, 59, 0, 0);
      document.getElementById("eventStart").value = this.formatDateForInput(startDate);
      document.getElementById("eventEnd").value = this.formatDateForInput(endDate);
    } else {
      document.getElementById("eventStart").value = this.formatDateForInput(start);
      document.getElementById("eventEnd").value = this.formatDateForInput(end);
    }
  
    this.modal.show();
  }

  openEditModal(eventData) {
    console.log("Ouverture du modal d'édition pour:", eventData);

    // Toujours récupérer l'événement complet depuis le backend pour avoir les bonnes heures
    this.backend.getEvent(eventData.id, eventData.calendarId, (error, completeEvent) => {
      if (error) {
        console.error("Erreur lors de la récupération des données complètes pour édition:", error);
        // En cas d'erreur, utiliser les données disponibles en fallback
        this.setupEditModal(eventData);
        return;
      }

      // Utiliser les données récupérées du backend (heures exactes)
      console.log("Données récupérées du backend pour édition:", completeEvent);
      this.setupEditModal(completeEvent, true);
    });
  }

  setupEditModal(event, fromBackend = false) {
    this.modalTitle.textContent = "Modifier un événement";
    this.deleteEventBtn.classList.remove("d-none");
    this.editEventId.value = event.id;
    document.getElementById("eventTitle").value = event.title;
    document.getElementById("originalCalendarId").value = event.calendarId;
    document.getElementById("eventCalendar").value = event.calendarId;

    // Utiliser directement les chaînes ISO pour les inputs
    if (fromBackend && typeof event.start === "string" && typeof event.end === "string") {
      // Extraire juste la partie YYYY-MM-DDThh:mm de la chaîne ISO
      document.getElementById("eventStart").value = this.formatDateForInput(event.start);
      document.getElementById("eventEnd").value = this.formatDateForInput(event.end);

      console.log(
        "Heures ISO utilisées directement:",
        document.getElementById("eventStart").value,
        document.getElementById("eventEnd").value
      );
    } else {
      // Fallback au cas où les dates ne sont pas en format chaîne
      document.getElementById("eventStart").value = this.formatDateForInput(event.start);
      document.getElementById("eventEnd").value = this.formatDateForInput(event.end);
    }

    // Remplir la catégorie
    if (event.raw && event.raw.categoryId) {
      document.getElementById("eventCategory").value = event.raw.categoryId;
    } else if (event.categoryId) {
      document.getElementById("eventCategory").value = event.categoryId;
    }

    // Réinitialiser le flag de duplication si présent
    let duplicateFlag = document.getElementById("isDuplicatedEvent");
    if (duplicateFlag) {
      duplicateFlag.value = "false";
    }

    this.modal.show();
  }

  openCloneModal(eventData) {
    console.log("Ouverture du modal de duplication pour:", eventData);

    // Si l'événement n'a pas d'ID ou de calendarId, utiliser directement les données disponibles
    if (!eventData.id || !eventData.calendarId) {
      console.log("Utilisation directe des données disponibles pour la duplication");
      this.setupCloneModal(eventData);
      return;
    }

    // Récupérer l'événement complet depuis le backend
    this.backend.getEvent(eventData.id, eventData.calendarId, (error, completeEvent) => {
      if (error) {
        console.log("Utilisation des données locales pour la duplication");
        // Même en cas d'erreur, continuer avec les données disponibles
        this.setupCloneModal(eventData);
        return;
      }

      // Utiliser les données récupérées du backend (heures exactes)
      console.log("Données récupérées du backend pour duplication:", completeEvent);
      this.setupCloneModal(completeEvent, true);
    });
  }

  setupCloneModal(event, fromBackend = false) {
    // Configuration du modal
    this.modalTitle.textContent = "Dupliquer un événement";
    this.deleteEventBtn.classList.add("d-none");
    this.editEventId.value = "";

    // Ajouter un indicateur que c'est une duplication
    const form = document.getElementById("createEventModal").querySelector("form");
    let duplicateFlag = document.getElementById("isDuplicatedEvent");

    if (!duplicateFlag) {
      duplicateFlag = document.createElement("input");
      duplicateFlag.type = "hidden";
      duplicateFlag.id = "isDuplicatedEvent";
      form.appendChild(duplicateFlag);
    }
    duplicateFlag.value = "true";

    // Remplir les champs avec les données de l'événement
    document.getElementById("eventTitle").value = event.title;
    document.getElementById("eventCalendar").value = event.calendarId;

    // Utiliser directement les chaînes ISO pour les inputs
    if (fromBackend && typeof event.start === "string" && typeof event.end === "string") {
      // Extraire juste la partie YYYY-MM-DDThh:mm de la chaîne ISO
      document.getElementById("eventStart").value = this.formatDateForInput(event.start);
      document.getElementById("eventEnd").value = this.formatDateForInput(event.end);

      console.log(
        "Heures ISO utilisées directement:",
        document.getElementById("eventStart").value,
        document.getElementById("eventEnd").value
      );
    } else {
      // Fallback au cas où les dates ne sont pas en format chaîne
      document.getElementById("eventStart").value = this.formatDateForInput(event.start);
      document.getElementById("eventEnd").value = this.formatDateForInput(event.end);
    }

    // Remplir la catégorie
    if (event.raw && event.raw.categoryId) {
      document.getElementById("eventCategory").value = event.raw.categoryId;
    } else if (event.categoryId) {
      document.getElementById("eventCategory").value = event.categoryId;
    }

    this.modal.show();
  }
  
  /**
   * Initialise les cases à cocher pour la visibilité des calendriers
   * Cette méthode gère les actions AJAX pour mettre à jour la visibilité
   */
  initializeCalendarCheckboxes() {
    console.log("Initialisation des cases à cocher de calendrier");
    const checkboxes = document.querySelectorAll('.calendar-checkbox');
    
    checkboxes.forEach(checkbox => {
      checkbox.addEventListener('change', (e) => {
        const calendarId = e.target.value;
        const isVisible = e.target.checked;
        
        console.log(`Changement de visibilité du calendrier ${calendarId} à ${isVisible}`);
        
        // IMPORTANT - Mise à jour visuelle immédiate
        this.reloadEvents();
        
        // Mettre à jour la base de données via AJAX
        fetch(this.config.baseUrl + 'ajax-handler.php?action=toggle-calendar-visibility', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: `calendar_id=${calendarId}&visible=${isVisible ? 1 : 0}`
        })
        .then(response => response.json())
        .then(data => {
          if (!data.success) {
            console.error('Erreur lors de la mise à jour de la visibilité:', data.message);
            e.target.checked = !isVisible;
            
            // Recharger à nouveau en cas d'erreur pour revenir à l'état précédent
            this.reloadEvents();
          }
        })
        .catch(error => {
          console.error('Erreur réseau:', error);
          e.target.checked = !isVisible;
          
          // Recharger à nouveau en cas d'erreur
          this.reloadEvents();
        });
      });
    });
  }
  
  /**
   * Fonctions d'interface utilisateur
   */
  updateCalendarHeader() {
    const currentDate = this.calendar.getDate();
    const months = [
      "Janvier", "Février", "Mars", "Avril", "Mai", "Juin",
      "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"
    ];
    const month = months[currentDate.getMonth()];
    const year = currentDate.getFullYear();

    document.getElementById("calendar-date-header").textContent = `${month} ${year}`;
  }

  updateViewButtons(viewName) {
    document.getElementById("day-view").classList.remove("active");
    document.getElementById("week-view").classList.remove("active");
    document.getElementById("month-view").classList.remove("active");

    if (viewName === "day") {
      document.getElementById("day-view").classList.add("active");
    } else if (viewName === "week") {
      document.getElementById("week-view").classList.add("active");
    } else if (viewName === "month") {
      document.getElementById("month-view").classList.add("active");
    }
  }

  initializeDatepicker() {
    jQuery(($) => {
      $("#datepicker").datepicker({
        format: "dd/mm/yyyy",
        language: "fr",
        autoclose: true,
        todayHighlight: true,
      });

      $("#date-picker-btn").click(() => {
        $("#datepicker").datepicker("show");
      });

      $("#datepicker").on("changeDate", (e) => {
        const selectedDate = e.date;
        this.calendar.setDate(selectedDate);
        this.updateCalendarHeader();
      });
    });
  }
  
  /**
   * Fonctions utilitaires pour les dates
   */
  formatLocalISOString(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    const hours = String(date.getHours()).padStart(2, "0");
    const minutes = String(date.getMinutes()).padStart(2, "0");
    const seconds = String(date.getSeconds()).padStart(2, "0");

    return `${year}-${month}-${day}T${hours}:${minutes}:${seconds}`;
  }
  
  getISOStringFromScheduleDate(scheduleDate) {
    if (!scheduleDate) {
      return this.formatLocalISOString(new Date());
    }

    // Gestion des objets TZDate
    if (scheduleDate && typeof scheduleDate === "object" && scheduleDate._date) {
      return this.formatLocalISOString(scheduleDate._date);
    }

    if (scheduleDate instanceof Date) {
      return this.formatLocalISOString(scheduleDate);
    }

    if (typeof scheduleDate === "string") {
      return scheduleDate;
    }

    console.warn("Format de date non reconnu", scheduleDate);
    return this.formatLocalISOString(new Date());
  }
  
  formatDateForInput(dateValue) {
    if (!dateValue) return "";

    // Gestion des objets TZDate de TUI Calendar
    if (dateValue && typeof dateValue === "object" && dateValue._date) {
      // TZDate contient une propriété _date qui est un Date standard
      return this.formatDateForInput(dateValue._date);
    }

    // Si c'est une chaîne ISO, extraire la partie pertinente
    if (typeof dateValue === "string") {
      // Pour les chaînes ISO, extraire YYYY-MM-DDThh:mm
      if (dateValue.includes("T")) {
        // Tronquer à YYYY-MM-DDThh:mm
        return dateValue.substring(0, 16);
      }

      // Pour les autres formats de chaîne, créer un objet Date
      dateValue = new Date(dateValue);
    }

    // Si c'est un objet Date, formater pour l'input datetime-local
    if (dateValue instanceof Date) {
      const year = dateValue.getFullYear();
      const month = String(dateValue.getMonth() + 1).padStart(2, "0");
      const day = String(dateValue.getDate()).padStart(2, "0");
      const hours = String(dateValue.getHours()).padStart(2, "0");
      const minutes = String(dateValue.getMinutes()).padStart(2, "0");
      return `${year}-${month}-${day}T${hours}:${minutes}`;
    }

    console.error("Format de date invalide:", dateValue);
    return "";
  }
}

// Créer directement au chargement du script
window.calendarApp = new CalendarFrontend();
console.log("App créée directement");

// Initialiser manuellement après un délai
setTimeout(function() {
  if (window.calendarApp) {
    try {
      console.log("Tentative d'initialisation forcée");
      window.calendarApp.initialize();
    } catch (error) {
      console.error("ERREUR D'INITIALISATION FORCÉE:", error);
    }
  }
}, 1000);