-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 02-05-2025 a las 11:43:30
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `tui_calendar_db`
--

--
-- Volcado de datos para la tabla `categories`
--

INSERT INTO `categories` (`category_id`, `name`, `color`, `bg_color`, `drag_bg_color`, `border_color`) VALUES
(1, 'Réunion', '#f20202', '#34495e', '#FFFFFF', '#2c3e50'),
(2, 'Rendez-vous', '#8E44AD', '#9B59B6', '#9B59B6', '#8E44AD'),
(3, 'Évènement', '#0046d1', '#e77b23', '#FFFFFF', '#d35400'),
(4, 'Rappel', '#16A085', '#1ABC9C', '#1ABC9C', '#16A085'),
(7, 'edu', '#eb0017', '#c5d737', '#FFFFFF', '#3788d8'),
(8, 'rdv', '#000000', '#37d767', '#FFFFFF', '#d7ca37');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
