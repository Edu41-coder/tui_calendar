/**
 * Script principal du calendrier TUI
 * Ce fichier contient toute la logique JavaScript pour le calendrier
 */

document.addEventListener("DOMContentLoaded", function () {
    // Variables globales
    let attachHandlersTimeout = null;
    let arrowClickInProgress = false;
  
    // Initialiser le backend
    CalendarBackend.init({
      baseUrl: "/tui_calendar/",
    });
  
    // Récupérer les données du calendrier et des catégories injectées par PHP
    const calendarData = window.calendarData || [];
    const calendarIds = window.calendarIds || [];
  
    // Ajouter les styles pour les flèches d'extension
    addExtensionArrowStyles();
  
    // Initialiser le calendrier
    const calendar = initializeCalendar(calendarData);
  
    calendar.on('afterRender', function() {
      reloadEvents(calendar);
    });
  
    // Nettoyage de la sélection en vue mensuelle :
    // Si l'utilisateur clique ailleurs que sur une case sélectionnée ou un popup,
    // on retire les blocs de sélection du DOM pour éviter l'accumulation de sélections visuelles.
    document.addEventListener(
      "click",
      function (e) {
        if (!calendar) return;
  
        if (calendar.getViewName && calendar.getViewName() === "month") {
          // Si le clic n'est pas sur une sélection ou un popup
          const isSelection = e.target.closest(
            ".tui-full-calendar-month-guide-block, .tui-full-calendar-month-creation-guide"
          );
          const isPopup = e.target.closest(".tui-full-calendar-popup-container");
          if (!isSelection && !isPopup) {
            // Supprime toutes les sélections du mois
            document
              .querySelectorAll(
                ".tui-full-calendar-month-guide-block, .tui-full-calendar-month-creation-guide"
              )
              .forEach((el) => {
                el.parentNode && el.parentNode.removeChild(el);
                // ou, si tu veux juste masquer :
                // el.style.display = "none";
              });
          }
        }
      },
      true
    );
    document.addEventListener(
      "click",
      function (e) {
        if (!calendar) return;
    
        if (calendar.getViewName && calendar.getViewName() === "week") {
          // Si le clic n'est pas sur une sélection ou un popup
          const isSelection = e.target.closest(".tui-full-calendar-daygrid-guide-creation-block");
          const isPopup = e.target.closest(".tui-full-calendar-popup-container");
          if (!isSelection && !isPopup) {
            // Supprime toutes les sélections de la semaine
            document
              .querySelectorAll(".tui-full-calendar-daygrid-guide-creation-block")
              .forEach((el) => {
                el.parentNode && el.parentNode.removeChild(el);
              });
          }
        }
      },
      true
    );
    // Attacher tous les gestionnaires d'événements
    attachEventHandlers(calendar, calendarIds);
  
    // Initialiser l'interface utilisateur
    updateCalendarHeader(calendar);
    updateViewButtons("week");
  
    // Initialiser jQuery datepicker
    initializeDatepicker(calendar);
  
    // Initialiser les modaux
    initializeModals(calendar);
  
    // Charger les événements initiaux depuis le backend
    loadInitialEvents(calendar);
  
    /**
     * Recharge les événements pour la période affichée avec un traitement spécial pour la vue jour
     */
function reloadEvents(calendar) {
  const currentView = calendar.getViewName();
  
  // Obtenir la plage de dates affichée réellement
  const rangeStart = calendar.getDateRangeStart();
  const rangeEnd = calendar.getDateRangeEnd();
  
  // Convertir et corriger le décalage
  let startDate = rangeStart instanceof Date ? rangeStart : 
                 (rangeStart._date ? new Date(rangeStart._date) : new Date(rangeStart));
  
  let endDate = rangeEnd instanceof Date ? rangeEnd : 
               (rangeEnd._date ? new Date(rangeEnd._date) : new Date(rangeEnd));
               
  // IMPORTANT: Ajouter exactement 1 jour à la date de fin pour inclure tous les événements
  endDate = new Date(endDate);
  endDate.setDate(endDate.getDate() + 1);
  
  console.log(`Rechargement des événements pour la vue ${currentView} du ${startDate.toLocaleDateString()} au ${endDate.toLocaleDateString()}`);
  
  // Utiliser une approche unifiée pour toutes les vues
  CalendarBackend.loadEvents(startDate, endDate, function(error, events) {
    if (error) {
      console.error("Erreur lors du chargement des événements:", error);
      return;
    }
    
    console.log(`${events.length} événements chargés pour la vue ${currentView}`);
    
    // Vider le calendrier
    calendar.clear();
    
    if (!events || !events.length) return;
    
    // Créer tous les événements et forcer un rendu complet
    calendar.createSchedules(events.map(transformEvent));
    
    // CORRECTION: Forcer un rendu spécifique pour les deux dernières colonnes
    if (currentView === 'week') {
      setTimeout(function() {
        forceRenderWeekendColumns(calendar);
      }, 100);
    }
  }, currentView === "day"); // true pour préserver les heures dans la vue jour
}

// Fonction auxiliaire pour transformer un événement au format TUI Calendar
function transformEvent(event) {
  const isAllDay = event.isAllDay === true || event.isAllDay === 1 || event.isAllDay === "1";
  
  return {
    id: event.id,
    calendarId: event.calendarId,
    title: event.title,
    start: new Date(event.start),
    end: new Date(event.end),
    isAllDay: isAllDay,
    category: isAllDay ? "allday" : "time",
    raw: {
      calendarColor: event.calendarColor,
      categoryColor: event.categoryColor,
      categoryTextColor: event.categoryTextColor,
      categoryId: event.categoryId,
      location: event.location,
      body: event.body
    }
  };
}

// Fonction spéciale pour forcer le rendu des colonnes du weekend
function forceRenderWeekendColumns(calendar) {
  try {
    // Accès direct aux colonnes weekend via le DOM (solution radicale)
    const weekendDays = document.querySelectorAll('.tui-full-calendar-timegrid-container .tui-full-calendar-timegrid-columns .tui-full-calendar-timegrid-column:nth-last-child(-n+2)');
    
    if (weekendDays && weekendDays.length) {
      console.log(`Rendu forcé pour ${weekendDays.length} colonnes weekend`);
      
      // Forcer un refresh du DOM
      weekendDays.forEach(column => {
        // Bidouillage DOM: masquer puis réafficher pour forcer un re-rendu
        column.style.opacity = "0.99";
        setTimeout(() => {
          column.style.opacity = "";
        }, 10);
      });
      
      // HACK: forcer aussi le contrôleur interne à recalculer les événements
      if (calendar._controller) {
        setTimeout(() => {
          // Accéder à la vue timeGrid et forcer un rendu complet
          const timeGridView = calendar._controller.view.children.single('timeGrid');
          if (timeGridView && timeGridView.render) {
            timeGridView.render();
          }
        }, 50);
      }
    }
  } catch (e) {
    console.error("Erreur lors du rendu forcé des colonnes weekend:", e);
  }
}
  
    /**
     * Charge les événements initiaux depuis le backend
     */
    function loadInitialEvents(calendar) {
      // Calculer les dates de début et de fin pour le mois en cours
      const currentDate = calendar.getDate();
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
  
      CalendarBackend.loadEvents(start, end, function (error, events) {
        if (error) {
          console.error("Erreur lors du chargement des événements:", error);
          return;
        }
  
        if (events && events.length) {
          // Convertir les événements au format TUI Calendar et les ajouter
          const schedules = events.map((event) => {
            return {
              id: event.id,
              calendarId: event.calendarId,
              title: event.title,
              start: new Date(event.start),
              end: new Date(event.end),
              isAllDay: event.isAllDay,
              category: event.isAllDay ? "allday" : "time",
              raw: {
                calendarColor: event.calendarColor,
                categoryColor: event.categoryColor,
                categoryTextColor: event.categoryTextColor,
                categoryId: event.categoryId,
                location: event.location,
                body: event.body,
              },
            };
          });
  
          calendar.createSchedules(schedules);
        }
      });
    }
  
    /**
     * Ajoute les styles CSS pour les flèches d'extension
     */
    function addExtensionArrowStyles() {
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
    function initializeCalendar(calendarData) {
      return new tui.Calendar("#calendar", {
        defaultView: "week",
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
          allday: function (schedule) {
            return schedule.title;
          },
          alldayTitle: function () {
            return '<div style="text-align: center; width: 100%;">Toute la journée</div>';
          },
  
          time: function (schedule) {
            const currentView = calendar.getViewName(); // Ajouté pour éviter l'erreur
  
            // Afficher les flèches uniquement en vue "week" et pour les événements "time"
            if (currentView !== "week" || schedule.category !== "time") {
              // Affichage normal sans flèches
              return `
                  <div class="event-content" data-schedule-id="${
                    schedule.id
                  }" data-calendar-id="${schedule.calendarId}" style="
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
          },
  
          monthGridSchedule: function (schedule) {
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
          },
        },
      });
    }
  
    /**
     * Attache tous les gestionnaires d'événements
     */
    function attachEventHandlers(calendar, calendarIds) {
      // Clic sur les flèches d'extension
      document.addEventListener(
        "click",
        function (e) {
          const arrow = e.target.closest(".event-extension-arrow");
          if (arrow) {
            // Définir un flag global avec une plus longue durée
            arrowClickInProgress = true;
            setTimeout(() => {
              arrowClickInProgress = false;
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
                handleArrowClick(
                  calendar,
                  isLeft ? "left" : "right",
                  scheduleData
                );
              } catch (error) {
                console.error("Erreur de parsing des données:", error);
              }
            }
  
            return false;
          }
        },
        true
      );
  
      // Clic droit sur les événements (menu contextuel)
      document.addEventListener(
        "contextmenu",
        function (e) {
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
            for (const calId of calendarIds) {
              try {
                const schedule = calendar.getSchedule(
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
                  const schedule = calendar.getSchedule(scheduleId, calendarId);
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
              CalendarBackend.getEvent(
                scheduleId,
                foundEvent.calendarId,
                function (error, eventData) {
                  if (!error && eventData) {
                    // Si on a réussi à récupérer les données, ouvrir le modal avec ces données
                    openCloneModal({
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
              openCloneModal({
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
        },
        true
      );
  
      // Navigation entre les vues
      document.getElementById("day-view").addEventListener("click", () => {
        calendar.changeView("day");
        updateViewButtons("day");
        updateCalendarHeader(calendar);
        reloadEvents(calendar);
      });
  
      document.getElementById("week-view").addEventListener("click", () => {
        calendar.changeView("week");
        updateViewButtons("week");
        updateCalendarHeader(calendar);
        reloadEvents(calendar);
      });
  
      document.getElementById("month-view").addEventListener("click", () => {
        calendar.changeView("month");
        updateViewButtons("month");
        updateCalendarHeader(calendar);
        reloadEvents(calendar);
      });
  
      // Boutons de navigation
      document.getElementById("prev-btn").addEventListener("click", () => {
        calendar.prev();
        updateCalendarHeader(calendar);
        reloadEvents(calendar); 
        
      });
  
      document.getElementById("next-btn").addEventListener("click", () => {
        calendar.next();
        updateCalendarHeader(calendar);
        reloadEvents(calendar);       
          
        
    });
  
      document.getElementById("today-btn").addEventListener("click", () => {
        calendar.today();
        updateCalendarHeader(calendar);
        reloadEvents(calendar);
      });
  
      calendar.on("clickSchedule", function (e) {
        // Désactiver complètement le comportement par défaut du clic simple
        e.preventDefault && e.preventDefault();
        // Ne rien faire d'autre - l'édition se fera uniquement par double-clic
      });
  
      calendar.on("beforeCreateSchedule", function (eventObj) {
        const startDate = new Date(eventObj.start);
        const endDate = new Date(
          eventObj.end || new Date(startDate.getTime() + 60 * 60 * 1000)
        );
      
        // Détecte si c'est une sélection all day (vue mois ou eventObj.isAllDay)
        const currentView = calendar.getViewName();
        const isAllDay = currentView === "month" || eventObj.isAllDay;
      
        openCreateModal(startDate, endDate, isAllDay);
      });
  
      calendar.on("beforeUpdateSchedule", function (e) {
        const schedule = e.schedule;
        const changes = e.changes;
  
        console.log("Événement redimensionné ou déplacé:", schedule);
        console.log("Modifications:", changes);
  
        if (changes && (changes.start || changes.end)) {
          // Mettre à jour localement
          calendar.updateSchedule(schedule.id, schedule.calendarId, changes);
  
          // Envoyer au backend
          const moveData = {
            id: schedule.id,
            calendarId: schedule.calendarId,
            start: changes.start || schedule.start,
            end: changes.end || schedule.end,
          };
  
          CalendarBackend.moveEvent(moveData, function (error, response) {
            if (error) {
              console.error("Erreur lors du déplacement:", error);
              // Vous pourriez revenir à l'état précédent si nécessaire
            }
          });
  
          console.log(
            "Nouvel horaire:",
            changes.start
              ? formatDateForInput(changes.start)
              : formatDateForInput(schedule.start),
            "à",
            changes.end
              ? formatDateForInput(changes.end)
              : formatDateForInput(schedule.end)
          );
        }
      });
  
      // Gestionnaires pour déplacements et redimensionnements
      calendar.on("moveSchedule", function (event) {
        console.log("Événement déplacé");
        if (calendar.getViewName() !== "week") {
          return;
        }
      });
  
      calendar.on("resizeSchedule", function (event) {
        console.log("Événement redimensionné");
        if (calendar.getViewName() !== "week") {
          return;
        }
      });
  
      // Gestionnaire du bouton Enregistrer
      document.getElementById("saveEventBtn").onclick = function () {
        saveEvent(calendar);
      };
  
      // Gestionnaire du bouton Supprimer
      document.getElementById("deleteEventBtn").onclick = function () {
        const confirmModal = new bootstrap.Modal(
          document.getElementById("confirmDeleteModal")
        );
        confirmModal.show();
      };
  
      // Gestionnaire de confirmation de suppression
      document.getElementById("confirmDeleteEventBtn").onclick = function () {
        const eventId = document.getElementById("editEventId").value;
        const calendarId = document.getElementById("eventCalendar").value;
  
        if (eventId) {
          // D'abord appeler le backend
          CalendarBackend.deleteEvent(
            eventId,
            calendarId,
            function (error, response) {
              if (!error) {                
                reloadEvents(calendar); 
              } else {
                console.error(
                  "Erreur lors de la suppression de l'événement:",
                  error
                );
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
            }
          );
        }
      };
  
      // Gestionnaire pour le double-clic (édition d'événement)
      document.querySelector("#calendar").addEventListener(
        "dblclick",
        function (e) {
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
          CalendarBackend.getEvent(
            scheduleId,
            calendarId,
            function (error, eventData) {
              if (error || !eventData) {
                console.error(
                  "Erreur lors de la récupération des données de l'événement pour édition:",
                  error
                );
  
                // Fallback - essayer de récupérer depuis le frontend
                let foundEvent = null;
                for (const calId of calendarIds) {
                  try {
                    const schedule = calendar.getSchedule(
                      scheduleId,
                      calId.toString()
                    );
                    if (schedule) {
                      foundEvent = schedule;
                      break;
                    }
                  } catch (err) {
                    // Ignorer les erreurs et continuer
                  }
                }
  
                if (foundEvent) {
                  openEditModal({
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
              openEditModal(eventData);
            }
          );
        },
        true
      );
    }
  
    /**
     * Ajoute des événements au calendrier depuis les données backend
     */
    function addEventsToCalendar(calendar, events) {
      calendar.clear();
      if (!events || !events.length) return;
  
      const schedules = events.map((event) => {
        return {
          id: event.id,
          calendarId: event.calendarId,
          title: event.title,
          start: new Date(event.start),
          end: new Date(event.end),
          isAllDay: event.isAllDay,
          category: event.isAllDay ? "allday" : "time",
          raw: {
            calendarColor: event.calendarColor,
            categoryColor: event.categoryColor,
            categoryTextColor: event.categoryTextColor,
            categoryId: event.categoryId,
            location: event.location,
            body: event.body,
          },
        };
      });
  
      calendar.createSchedules(schedules);
    }
  
    /**
     * Fonction pour enregistrer un événement (création, duplication ou modification)
     * @param {Object} calendar - Instance TUI Calendar
     */
    function saveEvent(calendar) {
      // Récupérer les valeurs du formulaire
      const title = document.getElementById("eventTitle").value;
      const startInput = document.getElementById("eventStart").value;
      const endInput = document.getElementById("eventEnd").value;
      const calendarId = document.getElementById("eventCalendar").value;
      const categoryId = document.getElementById("eventCategory").value;
      const eventId = document.getElementById("editEventId").value;
      const isAllDay = document.getElementById("eventAllDay").checked ? 1 : 0;
  
      // Vérifier si c'est un événement dupliqué
      const isDuplicated =
        document.getElementById("isDuplicatedEvent")?.value === "true";
  
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
          categoryColor =
            categorySelect.options[i].style.backgroundColor || "#FFFFFF";
          categoryTextColor = categorySelect.options[i].style.color || "#000000";
          break;
        }
      }
  
      // Récupérer la couleur du calendrier
      let calendarColor = "#FFFFFF";
      const calendarSelect = document.getElementById("eventCalendar");
      for (let i = 0; i < calendarSelect.options.length; i++) {
        if (calendarSelect.options[i].value === calendarId) {
          calendarColor =
            calendarSelect.options[i].style.backgroundColor || "#FFFFFF";
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
      CalendarBackend.saveEvent(eventData, function (error, response) {
        if (error) {
          console.error("Erreur lors de l'enregistrement:", error);
          return;
        }
           
        reloadEvents(calendar);
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
     * Gère les clics sur les flèches d'extension
     */
    function handleArrowClick(calendar, direction, scheduleData) {
      console.log(
        `Clic sur flèche ${direction} détecté pour l'événement:`,
        scheduleData
      );
  
      // Vérification des données minimales requises
      if (!scheduleData || !scheduleData.id || !scheduleData.calendarId) {
        console.error(
          "Données insuffisantes pour la duplication par flèche:",
          scheduleData
        );
        return;
      }
  
      const dayOffset = direction === "left" ? -1 : 1;
  
      // Récupérer l'événement depuis le backend
      CalendarBackend.getEvent(
        scheduleData.id,
        scheduleData.calendarId,
        function (error, eventData) {
          if (error) {
            console.error("Erreur lors de la récupération des données:", error);
  
            // Fallback: utiliser les données disponibles
            if (scheduleData.start && scheduleData.end) {
              duplicateWithLocalData(scheduleData);
            }
            return;
          }
  
          // Utiliser les données du backend
          duplicateWithBackendData(eventData);
        }
      );
  
      // Fonction pour dupliquer avec les données du backend
      function duplicateWithBackendData(eventData) {
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
  
        createDuplicateEvent(newEventData);
      }
  
      // Fonction pour dupliquer avec les données locales (fallback)
      function duplicateWithLocalData(data) {
        // Extraire les dates, en gérant le cas TZDate
        const getDateFromScheduleDate = function (dateValue) {
          if (dateValue && dateValue._date) return new Date(dateValue._date);
          if (dateValue instanceof Date) return new Date(dateValue);
          return new Date(dateValue);
        };
  
        const startDate = getDateFromScheduleDate(data.start);
        const endDate = getDateFromScheduleDate(data.end);
  
        // Ajouter le décalage de jour
        startDate.setDate(startDate.getDate() + dayOffset);
        endDate.setDate(endDate.getDate() + dayOffset);
  
        console.log(
          "Création d'une copie (fallback) avec dates:",
          startDate,
          "à",
          endDate
        );
  
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
  
        createDuplicateEvent(newEventData);
      }
  
      // Fonction commune pour créer l'événement
      function createDuplicateEvent(eventData) {
        // Envoyer au backend
        CalendarBackend.saveEvent(eventData, function (error, response) {
          if (error) {
            console.error("Erreur lors de la création de la copie:", error);
            return;
          }
           
          reloadEvents(calendar);
        });
      }
    }
  
    /**
     * Fonction utilitaire pour extraire la date ISO d'un objet schedule de TUI Calendar
     * qui peut avoir différentes structures selon la provenance de l'événement
     */
    function getISOStringFromScheduleDate(scheduleDate) {
      if (!scheduleDate) {
        return formatLocalISOString(new Date());
      }
  
      // Gestion des objets TZDate
      if (
        scheduleDate &&
        typeof scheduleDate === "object" &&
        scheduleDate._date
      ) {
        return formatLocalISOString(scheduleDate._date);
      }
  
      if (scheduleDate instanceof Date) {
        return formatLocalISOString(scheduleDate);
      }
  
      if (typeof scheduleDate === "string") {
        return scheduleDate;
      }
  
      console.warn("Format de date non reconnu", scheduleDate);
      return formatLocalISOString(new Date());
    }
    /**
     * Formate une Date en chaîne ISO locale (sans Z)
     */
    function formatLocalISOString(date) {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, "0");
      const day = String(date.getDate()).padStart(2, "0");
      const hours = String(date.getHours()).padStart(2, "0");
      const minutes = String(date.getMinutes()).padStart(2, "0");
      const seconds = String(date.getSeconds()).padStart(2, "0");
  
      return `${year}-${month}-${day}T${hours}:${minutes}:${seconds}`;
    }
    /**
     * Fonction utilitaire pour formater une date pour les inputs HTML
     * Supporte Date standard, TZDate et chaînes ISO
     */
    function formatDateForInput(dateValue) {
      if (!dateValue) return "";
  
      // Gestion des objets TZDate de TUI Calendar
      if (dateValue && typeof dateValue === "object" && dateValue._date) {
        // TZDate contient une propriété _date qui est un Date standard
        return formatDateForInput(dateValue._date);
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
    /**
     * Fonctions pour les modaux
     */
    function initializeModals() {
      window.modal = new bootstrap.Modal(
        document.getElementById("createEventModal")
      );
      window.modalTitle = document.getElementById("createEventModalLabel");
      window.editEventId = document.getElementById("editEventId");
      window.deleteEventBtn = document.getElementById("deleteEventBtn");
    }
  
    function openCreateModal(start, end, isAllDay = false) {
      modalTitle.textContent = "Créer un événement";
      deleteEventBtn.classList.add("d-none");
      editEventId.value = "";
    
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
        document.getElementById("eventStart").value = formatDateForInput(startDate);
        document.getElementById("eventEnd").value = formatDateForInput(endDate);
      } else {
        document.getElementById("eventStart").value = formatDateForInput(start);
        document.getElementById("eventEnd").value = formatDateForInput(end);
      }
    
      modal.show();
    }
  
    function openEditModal(eventData) {
      console.log("Ouverture du modal d'édition pour:", eventData);
  
      // Toujours récupérer l'événement complet depuis le backend pour avoir les bonnes heures
      CalendarBackend.getEvent(
        eventData.id,
        eventData.calendarId,
        function (error, completeEvent) {
          if (error) {
            console.error(
              "Erreur lors de la récupération des données complètes pour édition:",
              error
            );
            // En cas d'erreur, utiliser les données disponibles en fallback
            setupEditModal(eventData);
            return;
          }
  
          // Utiliser les données récupérées du backend (heures exactes)
          console.log(
            "Données récupérées du backend pour édition:",
            completeEvent
          );
          setupEditModal(completeEvent, true);
        }
      );
    }
  
    function setupEditModal(event, fromBackend = false) {
      modalTitle.textContent = "Modifier un événement";
      deleteEventBtn.classList.remove("d-none");
      editEventId.value = event.id;
      document.getElementById("eventTitle").value = event.title;
      document.getElementById("originalCalendarId").value = event.calendarId;
      document.getElementById("eventCalendar").value = event.calendarId;
  
      // Utiliser directement les chaînes ISO pour les inputs
      if (
        fromBackend &&
        typeof event.start === "string" &&
        typeof event.end === "string"
      ) {
        // Extraire juste la partie YYYY-MM-DDThh:mm de la chaîne ISO
        document.getElementById("eventStart").value = formatDateForInput(
          event.start
        );
        document.getElementById("eventEnd").value = formatDateForInput(event.end);
  
        console.log(
          "Heures ISO utilisées directement:",
          document.getElementById("eventStart").value,
          document.getElementById("eventEnd").value
        );
      } else {
        // Fallback au cas où les dates ne sont pas en format chaîne
        document.getElementById("eventStart").value = formatDateForInput(
          event.start
        );
        document.getElementById("eventEnd").value = formatDateForInput(event.end);
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
  
      modal.show();
    }
  
    window.openCloneModal = function (eventData) {
      console.log("Ouverture du modal de duplication pour:", eventData);
  
      // Si l'événement n'a pas d'ID ou de calendarId, utiliser directement les données disponibles
      if (!eventData.id || !eventData.calendarId) {
        console.log(
          "Utilisation directe des données disponibles pour la duplication"
        );
        setupCloneModal(eventData);
        return;
      }
  
      // Récupérer l'événement complet depuis le backend
      CalendarBackend.getEvent(
        eventData.id,
        eventData.calendarId,
        function (error, completeEvent) {
          if (error) {
            console.log("Utilisation des données locales pour la duplication");
            // Même en cas d'erreur, continuer avec les données disponibles
            setupCloneModal(eventData);
            return;
          }
  
          // Utiliser les données récupérées du backend (heures exactes)
          console.log(
            "Données récupérées du backend pour duplication:",
            completeEvent
          );
          setupCloneModal(completeEvent, true);
        }
      );
    };
  
    function setupCloneModal(event, fromBackend = false) {
      // Configuration du modal
      modalTitle.textContent = "Dupliquer un événement";
      deleteEventBtn.classList.add("d-none");
      editEventId.value = "";
  
      // Ajouter un indicateur que c'est une duplication
      const form = document
        .getElementById("createEventModal")
        .querySelector("form");
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
      if (
        fromBackend &&
        typeof event.start === "string" &&
        typeof event.end === "string"
      ) {
        // Extraire juste la partie YYYY-MM-DDThh:mm de la chaîne ISO
        document.getElementById("eventStart").value = formatDateForInput(
          event.start
        );
        document.getElementById("eventEnd").value = formatDateForInput(event.end);
  
        console.log(
          "Heures ISO utilisées directement:",
          document.getElementById("eventStart").value,
          document.getElementById("eventEnd").value
        );
      } else {
        // Fallback au cas où les dates ne sont pas en format chaîne
        document.getElementById("eventStart").value = formatDateForInput(
          event.start
        );
        document.getElementById("eventEnd").value = formatDateForInput(event.end);
      }
  
      // Remplir la catégorie
      if (event.raw && event.raw.categoryId) {
        document.getElementById("eventCategory").value = event.raw.categoryId;
      } else if (event.categoryId) {
        document.getElementById("eventCategory").value = event.categoryId;
      }
  
      modal.show();
    }
  
    /**
     * Fonctions d'interface utilisateur
     */
    function updateCalendarHeader(calendar) {
      const currentDate = calendar.getDate();
      const months = [
        "Janvier",
        "Février",
        "Mars",
        "Avril",
        "Mai",
        "Juin",
        "Juillet",
        "Août",
        "Septembre",
        "Octobre",
        "Novembre",
        "Décembre",
      ];
      const month = months[currentDate.getMonth()];
      const year = currentDate.getFullYear();
  
      document.getElementById(
        "calendar-date-header"
      ).textContent = `${month} ${year}`;
    }
  
    function updateViewButtons(viewName) {
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
  
    function initializeDatepicker(calendar) {
      jQuery(function ($) {
        $("#datepicker").datepicker({
          format: "dd/mm/yyyy",
          language: "fr",
          autoclose: true,
          todayHighlight: true,
        });
  
        $("#date-picker-btn").click(function () {
          $("#datepicker").datepicker("show");
        });
  
        $("#datepicker").on("changeDate", function (e) {
          const selectedDate = e.date;
          calendar.setDate(selectedDate);
          updateCalendarHeader(calendar);
        });
      });
    }
  });
