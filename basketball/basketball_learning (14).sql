-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Май 19 2026 г., 17:05
-- Версия сервера: 10.4.32-MariaDB
-- Версия PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `basketball_learning`
--

-- --------------------------------------------------------

--
-- Структура таблицы `balance_transactions`
--

CREATE TABLE `balance_transactions` (
  `id` int(11) NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `type` enum('credit','debit') NOT NULL COMMENT 'credit=надходження, debit=виведення',
  `amount` decimal(10,2) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `withdrawal_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `balance_transactions`
--

INSERT INTO `balance_transactions` (`id`, `trainer_id`, `type`, `amount`, `description`, `payment_id`, `withdrawal_id`, `created_at`) VALUES
(1, 9, 'credit', 800.00, 'Оплата курсу: Надпотужний курс (після комісії 20%)', 22, NULL, '2026-05-01 20:25:54'),
(2, 7, 'credit', 18761.60, 'Оплата курсу: Перший (після комісії 20%)', 20, NULL, '2026-05-01 20:26:37'),
(3, 7, 'debit', 761.00, 'Запит на виведення коштів', NULL, 1, '2026-05-01 20:27:23'),
(4, 7, 'debit', 761.00, 'Запит на виведення коштів', NULL, 2, '2026-05-01 20:48:51'),
(5, 7, 'debit', 761.00, 'Запит на виведення коштів', NULL, 3, '2026-05-01 20:49:59'),
(6, 7, 'debit', 761.00, 'Запит на виведення коштів', NULL, 4, '2026-05-01 20:50:05'),
(7, 7, 'credit', 761.00, 'Повернення коштів: запит на виведення відхилено. ', NULL, 4, '2026-05-01 20:51:02'),
(8, 7, 'credit', 761.00, 'Повернення коштів: запит на виведення відхилено. ', NULL, 3, '2026-05-01 20:51:06'),
(9, 7, 'credit', 761.00, 'Повернення коштів: запит на виведення відхилено. ', NULL, 2, '2026-05-01 20:51:08'),
(10, 3, 'credit', 1600.00, 'Оплата курсу: Техніка кидків (після комісії 20%)', 23, NULL, '2026-05-02 09:27:51'),
(11, 7, 'credit', 560.00, 'Оплата курсу: Техніка Кидка (після комісії 20%)', 24, NULL, '2026-05-02 09:43:05'),
(12, 7, 'debit', 1560.00, 'Запит на виведення коштів', NULL, 5, '2026-05-02 09:46:28'),
(13, 7, 'debit', 1560.00, 'Запит на виведення коштів', NULL, 6, '2026-05-02 09:47:35'),
(14, 7, 'credit', 1560.00, 'Повернення коштів: запит на виведення відхилено. ', NULL, 6, '2026-05-02 09:47:56'),
(15, 3, 'credit', 1600.00, 'Оплата курсу: Техніка кидків (після комісії 20%)', 25, NULL, '2026-05-07 00:46:25'),
(16, 7, 'debit', 499.99, 'Запит на виведення коштів', NULL, 7, '2026-05-08 08:38:58'),
(17, 7, 'debit', 499.99, 'Запит на виведення коштів', NULL, 8, '2026-05-08 08:40:49'),
(18, 7, 'debit', 434.99, 'Запит на виведення коштів', NULL, 9, '2026-05-08 08:41:22');

-- --------------------------------------------------------

--
-- Структура таблицы `chats`
--

CREATE TABLE `chats` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `chat_type` enum('course','general','individual') DEFAULT 'course',
  `subject` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_message_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `chats`
--

INSERT INTO `chats` (`id`, `student_id`, `trainer_id`, `course_id`, `chat_type`, `subject`, `created_at`, `last_message_at`) VALUES
(1, 10, 7, 6, 'course', NULL, '2026-02-07 09:57:20', '2026-02-09 22:16:37'),
(2, 10, 9, 9, 'course', NULL, '2026-02-07 09:59:07', '2026-04-23 09:51:35'),
(3, 8, 7, 7, 'course', NULL, '2026-04-29 10:26:31', '2026-04-29 10:26:34'),
(4, 8, 9, 8, 'course', NULL, '2026-04-29 11:03:49', NULL),
(5, 11, 7, 10, 'course', NULL, '2026-05-02 09:44:08', '2026-05-02 09:44:52'),
(6, 8, 3, NULL, 'general', 'Індивідуальна консультація', '2026-05-05 18:10:15', '2026-05-05 18:10:15'),
(7, 8, 5, NULL, 'general', 'Індивідуальна консультація', '2026-05-05 18:11:21', '2026-05-05 18:11:21');

-- --------------------------------------------------------

--
-- Структура таблицы `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `chat_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `message_type` enum('text','image','video') DEFAULT 'text',
  `media_path` varchar(500) DEFAULT NULL,
  `media_thumbnail` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `individual_course_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `chat_id`, `sender_id`, `message`, `message_type`, `media_path`, `media_thumbnail`, `is_read`, `individual_course_id`, `created_at`) VALUES
(1, 1, 10, 'привет', 'text', NULL, NULL, 1, NULL, '2026-02-07 09:57:49'),
(2, 1, 10, 'я українець', 'text', NULL, NULL, 1, NULL, '2026-02-07 09:58:02'),
(3, 1, 7, 'ооу', 'text', NULL, NULL, 1, NULL, '2026-02-07 09:58:15'),
(4, 1, 7, 'єс', 'text', NULL, NULL, 1, NULL, '2026-02-07 09:58:18'),
(5, 1, 7, '1000', 'text', NULL, NULL, 1, NULL, '2026-02-07 09:58:30'),
(6, 2, 10, 'пквапва', 'text', NULL, NULL, 1, NULL, '2026-02-07 10:00:36'),
(7, 2, 10, 'вапвапва', 'text', NULL, NULL, 1, NULL, '2026-02-07 10:00:38'),
(8, 2, 10, '324234', 'text', NULL, NULL, 1, NULL, '2026-02-07 10:00:40'),
(9, 1, 7, 'іваав', 'text', NULL, NULL, 1, NULL, '2026-02-07 10:00:51'),
(10, 2, 10, 'віаів', 'text', NULL, NULL, 1, NULL, '2026-02-09 10:31:39'),
(11, 1, 10, '32423423', 'text', NULL, NULL, 1, NULL, '2026-02-09 10:31:46'),
(12, 1, 7, 'girl', 'image', 'uploads/chat_media/1/media_1770674905_698a5ad932216.png', NULL, 1, NULL, '2026-02-09 22:08:25'),
(13, 1, 10, '', 'video', 'uploads/chat_media/1/media_1770674988_698a5b2cf0b52.mp4', NULL, 1, NULL, '2026-02-09 22:09:49'),
(14, 1, 10, 'hgh', 'text', NULL, NULL, 1, NULL, '2026-02-09 22:11:08'),
(15, 1, 10, '', 'video', 'uploads/chat_media/1/media_1770675085_698a5b8d336a2.mp4', NULL, 1, NULL, '2026-02-09 22:11:25'),
(16, 1, 7, '', 'video', 'uploads/chat_media/1/media_1770675397_698a5cc5e4607.mp4', NULL, 1, NULL, '2026-02-09 22:16:37'),
(17, 2, 9, 'wdsgsfgsfg', 'text', NULL, NULL, 1, NULL, '2026-04-22 11:09:49'),
(18, 2, 9, '', 'image', 'uploads/chat_media/2/media_1776856202_69e8ac8aad65e.jpg', NULL, 1, NULL, '2026-04-22 11:10:02'),
(19, 2, 9, 'кеуе', 'text', NULL, NULL, 1, NULL, '2026-04-23 09:51:35'),
(20, 3, 8, '6', 'text', NULL, NULL, 1, NULL, '2026-04-29 10:26:34'),
(21, 5, 11, 'ей йоу', 'text', NULL, NULL, 1, NULL, '2026-05-02 09:44:16'),
(22, 5, 11, 'васап', 'text', NULL, NULL, 1, NULL, '2026-05-02 09:44:24'),
(23, 5, 7, 'хай', 'text', NULL, NULL, 1, NULL, '2026-05-02 09:44:36'),
(24, 5, 7, 'осьо отак кидать', 'video', 'uploads/chat_media/5/media_1777715092_69f5c794b5063.mp4', NULL, 1, NULL, '2026-05-02 09:44:52'),
(25, 6, 8, 'Вітаю! Я хотів би отримати індивідуальний курс від вас. Чи можемо обговорити деталі?', 'text', NULL, NULL, 0, NULL, '2026-05-05 18:10:15'),
(26, 7, 8, 'Вітаю! Я хотів би отримати індивідуальний курс від вас. Чи можемо обговорити деталі?', 'text', NULL, NULL, 0, NULL, '2026-05-05 18:11:21');

-- --------------------------------------------------------

--
-- Структура таблицы `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `is_free` tinyint(1) DEFAULT 0,
  `duration_weeks` int(11) DEFAULT NULL,
  `level` enum('beginner','intermediate','advanced') NOT NULL,
  `trainer_id` int(11) DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `courses`
--

INSERT INTO `courses` (`id`, `title`, `description`, `price`, `is_free`, `duration_weeks`, `level`, `trainer_id`, `thumbnail`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Основи баскетболу для початківців', 'Повний курс для тих, хто тільки починає свій шлях у баскетболі', 1500.00, 0, 8, 'beginner', 2, NULL, 1, '2026-01-01 13:16:52', '2026-01-01 13:16:52'),
(2, 'Техніка кидків', 'Удосконалення техніки кидків з різних позицій', 2000.00, 0, 6, 'intermediate', 3, NULL, 1, '2026-01-01 13:16:52', '2026-01-01 13:16:52'),
(3, 'Професійний дриблінг', 'Майстер-клас з ведення м\'яча на високому рівні', 2500.00, 0, 10, 'advanced', 2, NULL, 1, '2026-01-01 13:16:52', '2026-01-01 13:16:52'),
(4, 'Основи баскетболу', 'Освоїш основи з якими зможеш далі рухатись', 1.00, 0, 10, 'beginner', 7, NULL, 1, '2026-01-02 14:57:28', '2026-05-07 18:46:24'),
(5, 'Розвиток швидкості, загальної сили', 'З рекомендаціями від гравців НБА', 23452.00, 0, 23, 'advanced', 7, NULL, 1, '2026-01-02 20:11:48', '2026-05-07 18:41:34'),
(6, 'Вправи для загального розвитку', 'Різні вправи на дриблінг, кидок та інше', 0.00, 1, 10, 'beginner', 7, NULL, 1, '2026-01-03 11:48:02', '2026-05-07 18:34:53'),
(7, 'Покращення дриблінгу', 'Вправи на дриблінг та постійний зв&#039;язок з тренером', 0.00, 1, 2, 'advanced', 7, NULL, 1, '2026-01-18 19:53:08', '2026-05-07 18:31:57'),
(8, 'Надпотужний курс', 'потужність', 1000.00, 0, 5, 'advanced', 9, NULL, 1, '2026-01-18 20:19:48', '2026-01-18 20:19:48'),
(9, 'New', '7y288423-94-23-4', 0.00, 1, 10, 'beginner', 9, NULL, 1, '2026-01-20 18:30:27', '2026-01-20 18:30:27'),
(10, 'Техніка Кидка', 'Виведеш свій кидок на абсолютно новий рівень', 700.00, 0, 2, 'intermediate', 7, NULL, 1, '2026-05-02 09:29:46', '2026-05-02 09:29:46'),
(11, 'Personal 1', 'For someone', 500.00, 0, 2, 'intermediate', 7, NULL, 1, '2026-05-14 10:46:36', '2026-05-14 10:46:36');

-- --------------------------------------------------------

--
-- Структура таблицы `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `progress` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `enrollments`
--

INSERT INTO `enrollments` (`id`, `user_id`, `course_id`, `enrolled_at`, `completed_at`, `progress`) VALUES
(1, 6, 1, '2026-01-01 17:18:39', NULL, 0),
(2, 6, 4, '2026-01-02 16:00:55', NULL, 0),
(3, 6, 6, '2026-01-03 11:50:26', NULL, 0),
(4, 7, 6, '2026-01-16 10:56:39', NULL, 0),
(5, 8, 1, '2026-01-17 19:13:37', NULL, 0),
(6, 8, 6, '2026-01-17 19:13:54', '2026-01-18 20:15:58', 100),
(7, 8, 4, '2026-01-18 19:51:03', '2026-01-18 19:51:27', 100),
(8, 8, 7, '2026-01-18 19:56:02', '2026-01-18 19:57:12', 100),
(9, 8, 8, '2026-01-18 20:22:01', '2026-01-23 21:46:49', 100),
(10, 10, 9, '2026-01-20 18:32:24', '2026-01-26 20:47:14', 100),
(11, 10, 6, '2026-01-26 20:47:48', '2026-01-28 20:18:11', 100),
(12, 11, 6, '2026-05-01 10:33:22', NULL, 0),
(13, 11, 8, '2026-05-01 20:25:54', NULL, 0),
(14, 11, 5, '2026-05-01 20:26:37', NULL, 0),
(15, 11, 2, '2026-05-02 09:27:51', NULL, 0),
(16, 11, 10, '2026-05-02 09:43:05', NULL, 0),
(17, 8, 2, '2026-05-07 00:46:25', NULL, 0);

-- --------------------------------------------------------

--
-- Структура таблицы `lesson_progress`
--

CREATE TABLE `lesson_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `is_completed` tinyint(1) DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `lesson_progress`
--

INSERT INTO `lesson_progress` (`id`, `user_id`, `lesson_id`, `is_completed`, `completed_at`, `created_at`) VALUES
(1, 8, 1, 1, '2026-01-18 19:51:21', '2026-01-18 19:51:17'),
(2, 8, 2, 1, '2026-01-18 19:51:33', '2026-01-18 19:51:27'),
(3, 8, 6, 1, '2026-01-18 19:56:16', '2026-01-18 19:56:13'),
(4, 8, 7, 1, '2026-01-18 19:56:51', '2026-01-18 19:56:51'),
(5, 8, 8, 1, '2026-01-18 19:57:12', '2026-01-18 19:57:12'),
(6, 8, 3, 1, '2026-01-18 20:15:45', '2026-01-18 20:15:45'),
(7, 8, 5, 1, '2026-01-18 20:15:54', '2026-01-18 20:15:54'),
(8, 8, 4, 1, '2026-01-18 20:15:58', '2026-01-18 20:15:58'),
(9, 10, 14, 1, '2026-01-20 18:32:37', '2026-01-20 18:32:37'),
(10, 8, 13, 1, '2026-01-22 17:51:47', '2026-01-22 17:51:47'),
(11, 8, 10, 1, '2026-01-23 21:46:41', '2026-01-23 21:46:41'),
(12, 8, 11, 1, '2026-01-23 21:46:43', '2026-01-23 21:46:43'),
(13, 8, 12, 1, '2026-01-23 21:46:45', '2026-01-23 21:46:45'),
(14, 8, 9, 1, '2026-01-23 21:46:49', '2026-01-23 21:46:49'),
(16, 10, 4, 1, '2026-01-26 20:48:00', '2026-01-26 20:48:00'),
(17, 10, 5, 1, '2026-01-26 20:48:06', '2026-01-26 20:48:06'),
(18, 10, 3, 1, '2026-01-28 20:18:11', '2026-01-28 20:18:11');

-- --------------------------------------------------------

--
-- Структура таблицы `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `platform_commission` decimal(10,2) DEFAULT 0.00,
  `trainer_amount` decimal(10,2) DEFAULT 0.00,
  `payment_method` enum('card','paypal','liqpay','other') NOT NULL DEFAULT 'liqpay',
  `transaction_id` varchar(255) DEFAULT NULL,
  `liqpay_order_id` varchar(255) DEFAULT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `payments`
--

INSERT INTO `payments` (`id`, `user_id`, `course_id`, `amount`, `platform_commission`, `trainer_amount`, `payment_method`, `transaction_id`, `liqpay_order_id`, `status`, `created_at`) VALUES
(1, 6, 1, 1500.00, 0.00, 0.00, 'card', 'TXN17672879197506', NULL, 'completed', '2026-01-01 17:18:39'),
(2, 6, 4, 3000.00, 0.00, 0.00, 'card', 'TXN17673696558149', NULL, 'completed', '2026-01-02 16:00:55'),
(3, 6, 6, 0.00, 0.00, 0.00, 'card', 'TXN17674410262839', NULL, 'completed', '2026-01-03 11:50:26'),
(4, 7, 6, 0.00, 0.00, 0.00, 'card', 'TXN17685609997653', NULL, 'completed', '2026-01-16 10:56:39'),
(5, 8, 1, 1500.00, 0.00, 0.00, 'card', 'TXN17686772172769', NULL, 'completed', '2026-01-17 19:13:37'),
(6, 8, 6, 0.00, 0.00, 0.00, 'card', 'TXN17686772344370', NULL, 'completed', '2026-01-17 19:13:54'),
(7, 8, 4, 3000.00, 0.00, 0.00, 'paypal', 'TXN17687658638575', NULL, 'completed', '2026-01-18 19:51:03'),
(8, 8, 8, 1000.00, 0.00, 0.00, 'card', 'TXN17687677216186', NULL, 'completed', '2026-01-18 20:22:01'),
(10, 8, 3, 2500.00, 500.00, 2000.00, 'liqpay', NULL, 'BSK_3_8_1777565128', 'pending', '2026-04-30 16:05:28'),
(16, 10, 4, 1.00, 0.20, 0.80, '', NULL, 'BSK4U10T1777632748', 'pending', '2026-05-01 10:52:28'),
(17, 12, 4, 1.00, 0.20, 0.80, '', NULL, 'BSK4U12T1777633154', 'pending', '2026-05-01 10:59:14'),
(18, 12, 8, 1000.00, 200.00, 800.00, '', NULL, 'BSK8U12T1777633331', 'pending', '2026-05-01 11:02:11'),
(19, 11, 4, 1.00, 0.20, 0.80, '', '1777664653', 'BSK4U11T1777664653', 'pending', '2026-05-01 19:44:13'),
(20, 11, 5, 23452.00, 4690.40, 18761.60, '', 'WFP_1777667197', 'BSK5U11T1777664684', 'completed', '2026-05-01 19:44:44'),
(21, 11, 1, 1500.00, 300.00, 1200.00, '', '1777666667', 'BSK1U11T1777666667', 'pending', '2026-05-01 20:17:47'),
(22, 11, 8, 1000.00, 200.00, 800.00, '', 'WFP_1777667154', 'BSK8U11T1777666945', 'completed', '2026-05-01 20:22:25'),
(23, 11, 2, 2000.00, 400.00, 1600.00, '', 'WFP_1777714071', 'BSK2U11T1777714001', 'completed', '2026-05-02 09:26:41'),
(24, 11, 10, 700.00, 140.00, 560.00, '', 'WFP_1777714985', 'BSK10U11T1777714958', 'completed', '2026-05-02 09:42:38'),
(25, 8, 2, 2000.00, 400.00, 1600.00, '', 'WFP_1778114785', 'BSK2U8T1778114718', 'completed', '2026-05-07 00:45:18'),
(26, 8, 5, 23452.00, 4690.40, 18761.60, '', '1778153671', 'BSK5U8T1778153671', 'pending', '2026-05-07 11:34:31'),
(27, 8, 11, 500.00, 100.00, 400.00, '', '1778755785', 'BSK11U8T1778755785', 'pending', '2026-05-14 10:49:45');

-- --------------------------------------------------------

--
-- Структура таблицы `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `course_id`, `rating`, `comment`, `created_at`) VALUES
(1, 8, 7, 5, 'надпотужно', '2026-01-23 21:45:41'),
(2, 8, 8, 5, 'супер курс. дуже сподобалось', '2026-01-23 21:47:13'),
(3, 10, 6, 4, '4wt45455454', '2026-02-22 18:51:13'),
(4, 8, 4, 3, 'r556546456456', '2026-04-30 08:45:05');

-- --------------------------------------------------------

--
-- Структура таблицы `trainer_balances`
--

CREATE TABLE `trainer_balances` (
  `id` int(11) NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `total_earned` decimal(10,2) DEFAULT 0.00 COMMENT 'Загальна сума заробітку',
  `available_balance` decimal(10,2) DEFAULT 0.00 COMMENT 'Доступно для виведення',
  `withdrawn_total` decimal(10,2) DEFAULT 0.00 COMMENT 'Вже виведено',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `trainer_balances`
--

INSERT INTO `trainer_balances` (`id`, `trainer_id`, `total_earned`, `available_balance`, `withdrawn_total`, `updated_at`) VALUES
(1, 2, 0.00, 0.00, 0.00, '2026-04-30 09:05:33'),
(2, 3, 3200.00, 3200.00, 0.00, '2026-05-07 00:46:25'),
(3, 5, 0.00, 0.00, 0.00, '2026-04-30 09:05:33'),
(4, 7, 19321.60, 15565.63, 2820.99, '2026-05-08 08:41:22'),
(5, 9, 800.00, 800.00, 0.00, '2026-05-01 20:25:54');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('student','trainer','admin') DEFAULT 'student',
  `avatar` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `experience_years` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `first_name`, `last_name`, `phone`, `role`, `avatar`, `bio`, `experience_years`, `created_at`, `updated_at`, `is_active`) VALUES
(1, 'admin@basketball.com', '$2y$10$bVya2QlVB7sX00r/0rJZmOqRHve.utUT0qllk5/mcHAzagUKvTp5u', 'Адмін', 'Система', '+380732176747', 'admin', NULL, '', NULL, '2026-01-01 13:16:51', '2026-05-04 12:35:36', 1),
(2, 'trainer1@basketball.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Олександр', 'Петренко', NULL, 'trainer', NULL, 'Професійний тренер з 10-річним досвідом', 10, '2026-01-01 13:16:52', '2026-01-01 13:16:52', 1),
(3, 'trainer2@basketball.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Марія', 'Коваленко', NULL, 'trainer', NULL, 'Експерт з техніки кидків та дриблінгу', 8, '2026-01-01 13:16:52', '2026-01-01 13:16:52', 1),
(4, 'student@basketball.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Іван', 'Шевченко', NULL, 'student', NULL, NULL, NULL, '2026-01-01 13:16:52', '2026-01-01 13:16:52', 1),
(5, 'mavpadan@gmail.com', '$2y$10$L422n81Gs4Zb6Zj8./aWVOZ2Zid73OGxbJyHYe78u0J98UVl0N5LC', 'Даніїл', 'Красільніков', '+380672176747', 'trainer', NULL, NULL, NULL, '2026-01-01 13:19:25', '2026-01-01 13:19:25', 1),
(6, 'patron@gmail.com', '$2y$10$mg9MiNNIopUfqrbvlGn.WOaAVBKq2iTXO1.AYs/NYF.letWxTXzvK', 'Пес', 'Патрон', '3402042304234', 'student', NULL, '&lt;br /&gt;\r\n&lt;b&gt;Warning&lt;/b&gt;:  Undefined array key &quot;bio&quot; in &lt;b&gt;D:appsxammpxammphtdocsbasketballprofile.php&lt;/b&gt; on line &lt;b&gt;433&lt;/b&gt;&lt;br /&gt;', NULL, '2026-01-01 17:17:46', '2026-01-02 14:41:46', 1),
(7, 'trener@gmail.com', '$2y$10$tBvSLXP4a/yAfu4ncrwC1OikLUlGfdcuWT7Xjfa9BF5C7CaYINvEa', 'Крутий', 'Тренер', '78908790', 'trainer', NULL, 'спмрл о\r\nрош\r\nиршгнщшошоз', 7, '2026-01-02 14:55:58', '2026-01-03 11:42:06', 1),
(8, 'student@gmail.com', '$2y$10$vXxuiRSOGGphu5Cl18QQmusqXGQGpcS.KJvD5/PDxlw3pzblaRAI.', 'Максим', 'Шевченко', '', 'student', NULL, '', NULL, '2026-01-17 19:11:55', '2026-05-07 17:47:31', 1),
(9, 'trenerovich@gmail.com', '$2y$10$HPwqb9ssLOJCPIGKPXKcNOU7RPZem.omPDWydkmoItmh.nijlKLIK', 'тренер', 'тренерович', '24567645325323', 'trainer', NULL, NULL, NULL, '2026-01-18 20:19:09', '2026-01-18 20:19:09', 1),
(10, 'proba@gmail.com', '$2y$10$xTv1CXaFjGJrEPJc7hX3fuvnJfGQb8VoNJB3iQ4GCJpL2jt6ClnRy', 'Proba', 'Probovich', '45346346346', 'student', NULL, '', NULL, '2026-01-20 17:27:51', '2026-04-22 10:55:16', 1),
(11, 'first@gmail.com', '$2y$10$YFVmv6rWzK7Oz5srQ.rObeU6US2V8ncQyfHkWovqrodPzEUxndfp2', 'Перший', 'Студент', '+380672176747', 'student', NULL, NULL, NULL, '2026-05-01 10:31:08', '2026-05-01 20:41:04', 1),
(12, 'stud@gmail.com', '$2y$10$XTeajl1quBVIxN939XV3puiW.tt0D6Jbo0SsacEgCSaaSErtxTGH.', '1', '2', '', 'student', NULL, NULL, NULL, '2026-05-01 10:59:02', '2026-05-01 10:59:02', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `video_lessons`
--

CREATE TABLE `video_lessons` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `video_file` varchar(255) DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `order_number` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `video_lessons`
--

INSERT INTO `video_lessons` (`id`, `course_id`, `title`, `description`, `video_url`, `video_file`, `duration_minutes`, `order_number`, `created_at`) VALUES
(1, 4, '1 урок', 'авпвапавп', 'https://www.youtube.com/watch?v=6mJuXovpU18', NULL, 10, 1, '2026-01-02 14:58:32'),
(2, 4, '2 урок', 'щлшпалалзщравпр', 'https://www.youtube.com/watch?v=j_fFo1FAW4M', NULL, 8, 2, '2026-01-02 15:00:53'),
(3, 6, 'first', 'выапвпфуавыми авпымисваы', NULL, '695902344f079_1767440948.mp4', 33, 1, '2026-01-03 11:49:08'),
(4, 6, 'second', '43t54324rtr', 'https://www.youtube.com/watch?v=6mJuXovpU18', NULL, 12, 2, '2026-01-03 11:49:37'),
(5, 6, 'third', 'sdfvbfdseefdgbgfder', NULL, '6959026a74567_1767441002.mp4', 3, 3, '2026-01-03 11:50:02'),
(6, 7, 'billy jean', 'fgdg', NULL, '696d3a6c3e4ff_1768766060.mp4', 0, 1, '2026-01-18 19:54:20'),
(7, 7, 'pes patron', 'sfdfsdfwef', NULL, '696d3a810140b_1768766081.mp4', 0, 2, '2026-01-18 19:54:41'),
(8, 7, 'opesok', 'gerfdv', NULL, '696d3a9745657_1768766103.mp4', 0, 3, '2026-01-18 19:55:03'),
(9, 8, '12', '', NULL, '696d407b28189_1768767611.mov', 1, 1, '2026-01-18 20:20:11'),
(10, 8, '2', '', NULL, '696d408e73fff_1768767630.mp4', 3, 2, '2026-01-18 20:20:30'),
(11, 8, '3', '', NULL, '696d409c2c28f_1768767644.mp4', 5, 3, '2026-01-18 20:20:44'),
(12, 8, '4', '', NULL, '696d40a9b138d_1768767657.mp4', 3, 4, '2026-01-18 20:20:57'),
(13, 8, '5', '', NULL, '696d4154066e3_1768767828.mp4', 0, 5, '2026-01-18 20:23:48'),
(14, 9, '1', 'dsfsdfsdfdsf', NULL, '696fc9efd7a5d_1768933871.mp4', 0, 1, '2026-01-20 18:31:11'),
(16, 10, 'Визначаємо твої особливості', 'Кожен кидок особливий', NULL, '69f5c69080801_1777714832.mp4', 1, 1, '2026-05-02 09:40:32'),
(17, 10, 'Показуємо приклади', 'Як це роблять професійні гравці', 'https://www.youtube.com/watch?v=iCWA6XpP1mo', NULL, 3, 2, '2026-05-02 09:41:35'),
(18, 10, 'Коментарі гравців', 'Проф спортсмени про особливості', 'https://www.youtube.com/watch?v=0T40jeBPWEQ', NULL, 17, 3, '2026-05-02 09:42:19');

-- --------------------------------------------------------

--
-- Структура таблицы `withdrawals`
--

CREATE TABLE `withdrawals` (
  `id` int(11) NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `card_number` varchar(20) NOT NULL COMMENT 'Номер картки для виплати',
  `card_holder` varchar(255) NOT NULL COMMENT 'ПІБ власника картки',
  `status` enum('pending','approved','rejected','paid') DEFAULT 'pending',
  `admin_note` text DEFAULT NULL COMMENT 'Коментар адміна',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `withdrawals`
--

INSERT INTO `withdrawals` (`id`, `trainer_id`, `amount`, `card_number`, `card_holder`, `status`, `admin_note`, `created_at`, `processed_at`) VALUES
(1, 7, 761.00, '4353453453453453', 'KRUTUI TRENER', 'paid', '+', '2026-05-01 20:27:23', '2026-05-01 20:51:22'),
(2, 7, 761.00, '4353453453453453', 'KRUTUI TRENER', 'rejected', '', '2026-05-01 20:48:51', '2026-05-01 20:51:08'),
(3, 7, 761.00, '4353453453453453', 'KRUTUI TRENER', 'rejected', '', '2026-05-01 20:49:59', '2026-05-01 20:51:06'),
(4, 7, 761.00, '4353453453453453', 'KRUTUI TRENER', 'rejected', '', '2026-05-01 20:50:05', '2026-05-01 20:51:02'),
(5, 7, 1560.00, '4545634634634634', 'KRUTUI TRENER', 'paid', 'виплачено', '2026-05-02 09:46:28', '2026-05-02 09:48:24'),
(6, 7, 1560.00, '4545634634634634', 'KRUTUI TRENER', 'rejected', '', '2026-05-02 09:47:35', '2026-05-02 09:47:56'),
(7, 7, 499.99, '4373473478748734', 'ІСІВІАВ', 'paid', '7277', '2026-05-08 08:38:58', '2026-05-08 08:40:43'),
(8, 7, 499.99, '4373473478748734', 'ІСІВІАВ', 'pending', NULL, '2026-05-08 08:40:49', NULL),
(9, 7, 434.99, '4534534534534534', 'D', 'pending', NULL, '2026-05-08 08:41:22', NULL);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `balance_transactions`
--
ALTER TABLE `balance_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_trainer` (`trainer_id`);

--
-- Индексы таблицы `chats`
--
ALTER TABLE `chats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_chat` (`student_id`,`trainer_id`,`course_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `trainer_id` (`trainer_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `idx_last_message` (`last_message_at`);

--
-- Индексы таблицы `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chat_id` (`chat_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `idx_read` (`is_read`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_message_type` (`message_type`);

--
-- Индексы таблицы `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_trainer` (`trainer_id`),
  ADD KEY `idx_level` (`level`);

--
-- Индексы таблицы `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_enrollment` (`user_id`,`course_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_course` (`course_id`);

--
-- Индексы таблицы `lesson_progress`
--
ALTER TABLE `lesson_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_progress` (`user_id`,`lesson_id`),
  ADD KEY `lesson_id` (`lesson_id`),
  ADD KEY `idx_user_lesson` (`user_id`,`lesson_id`);

--
-- Индексы таблицы `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_transaction` (`transaction_id`),
  ADD KEY `idx_liqpay_order` (`liqpay_order_id`);

--
-- Индексы таблицы `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_review` (`user_id`,`course_id`),
  ADD KEY `idx_course` (`course_id`),
  ADD KEY `idx_rating` (`rating`);

--
-- Индексы таблицы `trainer_balances`
--
ALTER TABLE `trainer_balances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_trainer` (`trainer_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`);

--
-- Индексы таблицы `video_lessons`
--
ALTER TABLE `video_lessons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_course` (`course_id`),
  ADD KEY `idx_order` (`course_id`,`order_number`);

--
-- Индексы таблицы `withdrawals`
--
ALTER TABLE `withdrawals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_trainer` (`trainer_id`),
  ADD KEY `idx_status` (`status`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `balance_transactions`
--
ALTER TABLE `balance_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT для таблицы `chats`
--
ALTER TABLE `chats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблицы `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT для таблицы `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT для таблицы `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT для таблицы `lesson_progress`
--
ALTER TABLE `lesson_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT для таблицы `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT для таблицы `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `trainer_balances`
--
ALTER TABLE `trainer_balances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT для таблицы `video_lessons`
--
ALTER TABLE `video_lessons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT для таблицы `withdrawals`
--
ALTER TABLE `withdrawals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `balance_transactions`
--
ALTER TABLE `balance_transactions`
  ADD CONSTRAINT `bt_trainer_fk` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `chats`
--
ALTER TABLE `chats`
  ADD CONSTRAINT `chats_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chats_ibfk_2` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chats_ibfk_3` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`chat_id`) REFERENCES `chats` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `lesson_progress`
--
ALTER TABLE `lesson_progress`
  ADD CONSTRAINT `lesson_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lesson_progress_ibfk_2` FOREIGN KEY (`lesson_id`) REFERENCES `video_lessons` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `trainer_balances`
--
ALTER TABLE `trainer_balances`
  ADD CONSTRAINT `tb_trainer_fk` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `video_lessons`
--
ALTER TABLE `video_lessons`
  ADD CONSTRAINT `video_lessons_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `withdrawals`
--
ALTER TABLE `withdrawals`
  ADD CONSTRAINT `wd_trainer_fk` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
