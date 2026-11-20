<?php
// Harden PHP session handling before starting the session.
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', $isHttps ? '1' : '0');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

$config = require __DIR__ . '/../config/config.php';
date_default_timezone_set($config['app']['timezone']);
define('BASE_URL', $config['app']['url']);
define('BRAND_COLOR', $config['app']['brand_color']);

if ($config['app']['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../core/Captcha.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../app/models/ActivityLog.php';
require_once __DIR__ . '/../app/core/Notifier.php';

$router = new Router();

// Auth
$router->add('login', ['controller' => 'AuthController', 'method' => 'loginForm']);
$router->add('login/submit', ['controller' => 'AuthController', 'method' => 'login']);
$router->add('logout', ['controller' => 'AuthController', 'method' => 'logout']);
$router->add('change-password', ['controller' => 'AuthController', 'method' => 'changePasswordForm']);
$router->add('change-password/submit', ['controller' => 'AuthController', 'method' => 'changePassword']);

// Panel
$router->add('dashboard', ['controller' => 'DashboardController', 'method' => 'index']);

// Public Site
$router->add('', ['controller' => 'SiteController', 'method' => 'home']);
$router->add('site/services', ['controller' => 'SiteController', 'method' => 'services']);
$router->add('site/gallery', ['controller' => 'SiteController', 'method' => 'gallery']);
$router->add('site/contact', ['controller' => 'SiteController', 'method' => 'contact']);
$router->add('site/consultation', ['controller' => 'SiteController', 'method' => 'consultationForm']);
$router->add('site/consultation/submit', ['controller' => 'SiteController', 'method' => 'consultationSubmit']);
$router->add('site/about', ['controller' => 'SiteController', 'method' => 'about']);

// Users
$router->add('users', ['controller' => 'UserController', 'method' => 'index']);
$router->add('users/create', ['controller' => 'UserController', 'method' => 'createForm']);
$router->add('users/create/submit', ['controller' => 'UserController', 'method' => 'create']);
$router->add('users/edit/([0-9]+)', ['controller' => 'UserController', 'method' => 'editForm']);
$router->add('users/edit/([0-9]+)/submit', ['controller' => 'UserController', 'method' => 'edit']);
$router->add('users/toggle/([0-9]+)', ['controller' => 'UserController', 'method' => 'toggle']);

// Services
$router->add('services', ['controller' => 'ServiceController', 'method' => 'index']);
$router->add('services/create', ['controller' => 'ServiceController', 'method' => 'createForm']);
$router->add('services/create/submit', ['controller' => 'ServiceController', 'method' => 'create']);
$router->add('services/edit/([0-9]+)', ['controller' => 'ServiceController', 'method' => 'editForm']);
$router->add('services/edit/([0-9]+)/submit', ['controller' => 'ServiceController', 'method' => 'edit']);
$router->add('services/toggle/([0-9]+)', ['controller' => 'ServiceController', 'method' => 'toggle']);

// Service Categories
$router->add('service-categories', ['controller' => 'ServiceCategoryController', 'method' => 'index']);
$router->add('service-categories/create', ['controller' => 'ServiceCategoryController', 'method' => 'createForm']);
$router->add('service-categories/create/submit', ['controller' => 'ServiceCategoryController', 'method' => 'create']);
$router->add('service-categories/edit/([0-9]+)', ['controller' => 'ServiceCategoryController', 'method' => 'editForm']);
$router->add('service-categories/edit/([0-9]+)/submit', ['controller' => 'ServiceCategoryController', 'method' => 'edit']);
$router->add('service-categories/toggle/([0-9]+)', ['controller' => 'ServiceCategoryController', 'method' => 'toggle']);
$router->add('service-categories/delete/([0-9]+)', ['controller' => 'ServiceCategoryController', 'method' => 'delete']);

// Clients
$router->add('clients', ['controller' => 'ClientController', 'method' => 'index']);
$router->add('clients/create', ['controller' => 'ClientController', 'method' => 'createForm']);
$router->add('clients/create/submit', ['controller' => 'ClientController', 'method' => 'create']);
$router->add('clients/edit/([0-9]+)', ['controller' => 'ClientController', 'method' => 'editForm']);
$router->add('clients/edit/([0-9]+)/submit', ['controller' => 'ClientController', 'method' => 'edit']);

// Holidays
$router->add('holidays', ['controller' => 'HolidayController', 'method' => 'index']);
$router->add('holidays/create', ['controller' => 'HolidayController', 'method' => 'createForm']);
$router->add('holidays/create/submit', ['controller' => 'HolidayController', 'method' => 'create']);
$router->add('holidays/edit/([0-9]+)', ['controller' => 'HolidayController', 'method' => 'editForm']);
$router->add('holidays/edit/([0-9]+)/submit', ['controller' => 'HolidayController', 'method' => 'edit']);
$router->add('holidays/toggle/([0-9]+)', ['controller' => 'HolidayController', 'method' => 'toggle']);
$router->add('holidays/calendar', ['controller' => 'HolidayController', 'method' => 'calendarData']);

// Settings
$router->add('settings', ['controller' => 'SettingController', 'method' => 'index']);
$router->add('settings/save', ['controller' => 'SettingController', 'method' => 'save']);

// Schedules
$router->add('schedules', ['controller' => 'ScheduleController', 'method' => 'index']);
$router->add('schedules/save', ['controller' => 'ScheduleController', 'method' => 'save']);

// Appointments
$router->add('appointments', ['controller' => 'AppointmentController', 'method' => 'index']);
$router->add('appointments/create', ['controller' => 'AppointmentController', 'method' => 'createForm']);
$router->add('appointments/create/submit', ['controller' => 'AppointmentController', 'method' => 'create']);
$router->add('appointments/show/([0-9]+)', ['controller' => 'AppointmentController', 'method' => 'show']);
$router->add('appointments/status/([0-9]+)', ['controller' => 'AppointmentController', 'method' => 'changeStatus']);
$router->add('appointments/search-client', ['controller' => 'AppointmentController', 'method' => 'searchClient']);
$router->add('appointments/service-operators/([0-9]+)', ['controller' => 'AppointmentController', 'method' => 'getServiceOperators']);
$router->add('appointments/available-slots', ['controller' => 'AppointmentController', 'method' => 'getAvailableSlots']);
$router->add('appointments/confirm-receipt/([0-9]+)', ['controller' => 'AppointmentController', 'method' => 'confirmReceipt']);
$router->add('appointments/suspend-cancel/([0-9]+)', ['controller' => 'AppointmentController', 'method' => 'suspendOrCancel']);
$router->add('appointments/awaiting/([0-9]+)', ['controller' => 'AppointmentController', 'method' => 'markAwaiting']);
$router->add('appointments/settle/([0-9]+)', ['controller' => 'AppointmentController', 'method' => 'settle']);
$router->add('appointments/approve-cancel/([0-9]+)', ['controller' => 'AppointmentController', 'method' => 'approveCancel']);
$router->add('appointments/reject-cancel/([0-9]+)', ['controller' => 'AppointmentController', 'method' => 'rejectCancel']);
$router->add('appointments/reschedule/([0-9]+)', ['controller' => 'AppointmentController', 'method' => 'rescheduleForm']);
$router->add('appointments/reschedule/([0-9]+)/submit', ['controller' => 'AppointmentController', 'method' => 'rescheduleSubmit']);
$router->add('appointments/reschedule-dates/([0-9]+)', ['controller' => 'AppointmentController', 'method' => 'rescheduleDates']);
$router->add('appointments/reschedule-slots/([0-9]+)', ['controller' => 'AppointmentController', 'method' => 'rescheduleSlots']);

$router->add('appointments/change-operator/([0-9]+)', ['controller' => 'AppointmentController', 'method' => 'changeOperator']);
// Operator
$router->add('operator/today', ['controller' => 'OperatorController', 'method' => 'today']);
$router->add('operator/search-client', ['controller' => 'OperatorController', 'method' => 'searchClient']);
$router->add('operator/close', ['controller' => 'OperatorController', 'method' => 'closeAppointment']);

// Accounting
$router->add('accounting', ['controller' => 'AccountingController', 'method' => 'index']);
$router->add('accounting/expense', ['controller' => 'AccountingController', 'method' => 'expenseForm']);
$router->add('accounting/expense/submit', ['controller' => 'AccountingController', 'method' => 'addExpense']);
$router->add('accounting/cash-register', ['controller' => 'AccountingController', 'method' => 'cashRegister']);
$router->add('accounting/cash-register/save', ['controller' => 'AccountingController', 'method' => 'saveCashRegister']);
$router->add('accounting/commissions', ['controller' => 'AccountingController', 'method' => 'commissions']);
$router->add('accounting/commissions/pay/([0-9]+)', ['controller' => 'AccountingController', 'method' => 'payCommission']);
$router->add('accounting/commissions/pay-all/([0-9]+)', ['controller' => 'AccountingController', 'method' => 'payAllCommissions']);
$router->add('accounting/salaries', ['controller' => 'SalaryController', 'method' => 'index']);
$router->add('accounting/salaries/create', ['controller' => 'SalaryController', 'method' => 'createForm']);
$router->add('accounting/salaries/create/submit', ['controller' => 'SalaryController', 'method' => 'create']);
$router->add('accounting/salaries/pay/([0-9]+)', ['controller' => 'SalaryController', 'method' => 'pay']);
$router->add('accounting/salaries/delete/([0-9]+)', ['controller' => 'SalaryController', 'method' => 'delete']);
$router->add('accounting/locks', ['controller' => 'FinancialLockController', 'method' => 'index']);
$router->add('accounting/locks/lock', ['controller' => 'FinancialLockController', 'method' => 'lock']);
$router->add('accounting/locks/unlock/([0-9]+)', ['controller' => 'FinancialLockController', 'method' => 'unlock']);
$router->add('accounting/invoices', ['controller' => 'InvoiceController', 'method' => 'index']);
$router->add('accounting/invoices/create', ['controller' => 'InvoiceController', 'method' => 'createForm']);
$router->add('accounting/invoices/create/submit', ['controller' => 'InvoiceController', 'method' => 'create']);
$router->add('accounting/invoices/show/([0-9]+)', ['controller' => 'InvoiceController', 'method' => 'show']);
$router->add('accounting/invoices/delete/([0-9]+)', ['controller' => 'InvoiceController', 'method' => 'delete']);
$router->add('accounting/invoices/search-client', ['controller' => 'InvoiceController', 'method' => 'searchClient']);
$router->add('accounting/invoices/client-appointments/([0-9]+)', ['controller' => 'InvoiceController', 'method' => 'getClientAppointments']);
$router->add('reports', ['controller' => 'ReportController', 'method' => 'index']);
$router->add('reports/revenue-data', ['controller' => 'ReportController', 'method' => 'revenueData']);
$router->add('reports/services-data', ['controller' => 'ReportController', 'method' => 'servicesData']);
$router->add('reports/operators-data', ['controller' => 'ReportController', 'method' => 'operatorsData']);


// Booking
$router->add('booking', ['controller' => 'BookingController', 'method' => 'index']);
$router->add('booking/verify', ['controller' => 'BookingController', 'method' => 'verifyClient']);
$router->add('booking/services', ['controller' => 'BookingController', 'method' => 'services']);
$router->add('booking/services/submit', ['controller' => 'BookingController', 'method' => 'servicesSubmit']);
$router->add('booking/schedule', ['controller' => 'BookingController', 'method' => 'schedule']);
$router->add('booking/schedule/submit', ['controller' => 'BookingController', 'method' => 'scheduleSubmit']);
$router->add('booking/operators/([0-9]+)', ['controller' => 'BookingController', 'method' => 'getOperators']);
$router->add('booking/dates', ['controller' => 'BookingController', 'method' => 'getDates']);
$router->add('booking/slots', ['controller' => 'BookingController', 'method' => 'getSlots']);
$router->add('booking/preview', ['controller' => 'BookingController', 'method' => 'preview']);
$router->add('booking/submit', ['controller' => 'BookingController', 'method' => 'submit']);
$router->add('booking/success', ['controller' => 'BookingController', 'method' => 'success']);
$router->add('booking/track', ['controller' => 'BookingController', 'method' => 'trackForm']);
$router->add('booking/track/submit', ['controller' => 'BookingController', 'method' => 'track']);
$router->add('booking/track/detail', ['controller' => 'BookingController', 'method' => 'trackDetail']);
$router->add('booking/track/cancel', ['controller' => 'BookingController', 'method' => 'cancelByClient']);
$router->add('booking/track/result', ['controller' => 'BookingController', 'method' => 'trackResult']);
$router->add('booking/track/logout', ['controller' => 'BookingController', 'method' => 'trackLogout']);
$router->add('booking/validate-discount', ['controller' => 'BookingController', 'method' => 'validateDiscount']);
$router->add('booking/remove-discount', ['controller' => 'BookingController', 'method' => 'removeDiscount']);

// Content Manager
$router->add('content/gallery', ['controller' => 'ContentController', 'method' => 'gallery']);
$router->add('content/gallery/create', ['controller' => 'ContentController', 'method' => 'galleryCreateForm']);
$router->add('content/gallery/create/submit', ['controller' => 'ContentController', 'method' => 'galleryCreate']);
$router->add('content/gallery/edit/([0-9]+)', ['controller' => 'ContentController', 'method' => 'galleryEditForm']);
$router->add('content/gallery/edit/([0-9]+)/submit', ['controller' => 'ContentController', 'method' => 'galleryEdit']);
$router->add('content/gallery/toggle/([0-9]+)', ['controller' => 'ContentController', 'method' => 'galleryToggle']);
$router->add('content/gallery/delete/([0-9]+)', ['controller' => 'ContentController', 'method' => 'galleryDelete']);
$router->add('content/comments', ['controller' => 'ContentController', 'method' => 'comments']);
$router->add('content/comments/reply/([0-9]+)', ['controller' => 'ContentController', 'method' => 'commentReply']);
$router->add('content/comments/delete/([0-9]+)', ['controller' => 'ContentController', 'method' => 'commentDelete']);

// Discounts
$router->add('discounts', ['controller' => 'DiscountController', 'method' => 'index']);
$router->add('discounts/create', ['controller' => 'DiscountController', 'method' => 'createForm']);
$router->add('discounts/create/submit', ['controller' => 'DiscountController', 'method' => 'create']);
$router->add('discounts/edit/([0-9]+)', ['controller' => 'DiscountController', 'method' => 'editForm']);
$router->add('discounts/edit/([0-9]+)/submit', ['controller' => 'DiscountController', 'method' => 'edit']);
$router->add('discounts/toggle/([0-9]+)', ['controller' => 'DiscountController', 'method' => 'toggle']);
$router->add('discounts/delete/([0-9]+)', ['controller' => 'DiscountController', 'method' => 'delete']);

// Backups
$router->add('backups', ['controller' => 'BackupController', 'method' => 'index']);
$router->add('backups/create', ['controller' => 'BackupController', 'method' => 'create']);
$router->add('backups/delete/([0-9]+)', ['controller' => 'BackupController', 'method' => 'delete']);
$router->add('backups/download/([0-9]+)', ['controller' => 'BackupController', 'method' => 'download']);
$router->add('backups/create-full', ['controller' => 'BackupController', 'method' => 'createFull']);

// Notification Templates
$router->add('notification-templates', ['controller' => 'NotificationTemplateController', 'method' => 'index']);
$router->add('notification-templates/create', ['controller' => 'NotificationTemplateController', 'method' => 'createForm']);
$router->add('notification-templates/create/submit', ['controller' => 'NotificationTemplateController', 'method' => 'create']);
$router->add('notification-templates/edit/([0-9]+)', ['controller' => 'NotificationTemplateController', 'method' => 'editForm']);
$router->add('notification-templates/edit/([0-9]+)/submit', ['controller' => 'NotificationTemplateController', 'method' => 'edit']);
$router->add('notification-templates/delete/([0-9]+)', ['controller' => 'NotificationTemplateController', 'method' => 'delete']);

// Consultations
$router->add('consultations', ['controller' => 'ConsultationController', 'method' => 'index']);
$router->add('consultations/show/([0-9]+)', ['controller' => 'ConsultationController', 'method' => 'show']);
$router->add('consultations/close/([0-9]+)', ['controller' => 'ConsultationController', 'method' => 'close']);

// Cron (بدون نیاز به لاگین)
$router->add('cron/backup', ['controller' => 'BackupController', 'method' => 'cron']);
$router->add('logs', ['controller' => 'ActivityLogController', 'method' => 'index']);

// Profile
$router->add('profile', ['controller' => 'ProfileController', 'method' => 'index']);
$router->add('profile/change-password', ['controller' => 'ProfileController', 'method' => 'changePassword']);
$router->add('profile/upload-photo', ['controller' => 'ProfileController', 'method' => 'uploadPhoto']);
$router->add('profile/delete-photo', ['controller' => 'ProfileController', 'method' => 'deletePhoto']);

$router->dispatch();