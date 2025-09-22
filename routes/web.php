<?php

Route::redirect('/', '/admin');

//Route::get('/', 'WebsiteController@index');

Auth::routes(['register' => false]);

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => 'Admin', 'middleware' => ['auth']], function () {

    Route::get('/', 'HomeController@index')->name('home');
    Route::get('company-dashboard', 'HomeController@companyDashboard');
    Route::get('company-invoice-dashboard', 'HomeController@companyInvoiceDashboard');
    Route::post('company-invoice-upload-media', 'HomeController@companyInvoiceUploadMedia')->name('company-invoice-upload-media');

    Route::get('/select-company/{company_id}', 'HomeController@selectCompany');

    // Permissions
    Route::delete('permissions/destroy', 'PermissionsController@massDestroy')->name('permissions.massDestroy');
    Route::resource('permissions', 'PermissionsController');

    // Roles
    Route::delete('roles/destroy', 'RolesController@massDestroy')->name('roles.massDestroy');
    Route::resource('roles', 'RolesController');

    // Users
    Route::delete('users/destroy', 'UsersController@massDestroy')->name('users.massDestroy');
    Route::post('users/parse-csv-import', 'UsersController@parseCsvImport')->name('users.parseCsvImport');
    Route::post('users/process-csv-import', 'UsersController@processCsvImport')->name('users.processCsvImport');
    Route::resource('users', 'UsersController');

    // User Alerts
    Route::delete('user-alerts/destroy', 'UserAlertsController@massDestroy')->name('user-alerts.massDestroy');
    Route::get('user-alerts/read', 'UserAlertsController@read');
    Route::resource('user-alerts', 'UserAlertsController', ['except' => ['edit', 'update']]);

    // Faq Category
    Route::delete('faq-categories/destroy', 'FaqCategoryController@massDestroy')->name('faq-categories.massDestroy');
    Route::post('faq-categories/parse-csv-import', 'FaqCategoryController@parseCsvImport')->name('faq-categories.parseCsvImport');
    Route::post('faq-categories/process-csv-import', 'FaqCategoryController@processCsvImport')->name('faq-categories.processCsvImport');
    Route::resource('faq-categories', 'FaqCategoryController');

    // Faq Question
    Route::delete('faq-questions/destroy', 'FaqQuestionController@massDestroy')->name('faq-questions.massDestroy');
    Route::post('faq-questions/parse-csv-import', 'FaqQuestionController@parseCsvImport')->name('faq-questions.parseCsvImport');
    Route::post('faq-questions/process-csv-import', 'FaqQuestionController@processCsvImport')->name('faq-questions.processCsvImport');
    Route::resource('faq-questions', 'FaqQuestionController');

    // Cars
    Route::delete('cars/destroy', 'CarsController@massDestroy')->name('cars.massDestroy');
    Route::post('cars/media', 'CarsController@storeMedia')->name('cars.storeMedia');
    Route::post('cars/ckmedia', 'CarsController@storeCKEditorImages')->name('cars.storeCKEditorImages');
    Route::post('cars/parse-csv-import', 'CarsController@parseCsvImport')->name('cars.parseCsvImport');
    Route::post('cars/process-csv-import', 'CarsController@processCsvImport')->name('cars.processCsvImport');
    Route::resource('cars', 'CarsController');

    // Car Rental Contact Requests
    Route::delete('car-rental-contact-requests/destroy', 'CarRentalContactRequestsController@massDestroy')->name('car-rental-contact-requests.massDestroy');
    Route::resource('car-rental-contact-requests', 'CarRentalContactRequestsController');

    // Hero Banner
    Route::delete('hero-banners/destroy', 'HeroBannerController@massDestroy')->name('hero-banners.massDestroy');
    Route::post('hero-banners/media', 'HeroBannerController@storeMedia')->name('hero-banners.storeMedia');
    Route::post('hero-banners/ckmedia', 'HeroBannerController@storeCKEditorImages')->name('hero-banners.storeCKEditorImages');
    Route::resource('hero-banners', 'HeroBannerController');

    // Home Info
    Route::delete('home-infos/destroy', 'HomeInfoController@massDestroy')->name('home-infos.massDestroy');
    Route::post('home-infos/media', 'HomeInfoController@storeMedia')->name('home-infos.storeMedia');
    Route::post('home-infos/ckmedia', 'HomeInfoController@storeCKEditorImages')->name('home-infos.storeCKEditorImages');
    Route::resource('home-infos', 'HomeInfoController');

    // Activities
    Route::delete('activities/destroy', 'ActivitiesController@massDestroy')->name('activities.massDestroy');
    Route::resource('activities', 'ActivitiesController');

    // Testimonials
    Route::delete('testimonials/destroy', 'TestimonialsController@massDestroy')->name('testimonials.massDestroy');
    Route::resource('testimonials', 'TestimonialsController');

    // Own Car
    Route::delete('own-cars/destroy', 'OwnCarController@massDestroy')->name('own-cars.massDestroy');
    Route::post('own-cars/media', 'OwnCarController@storeMedia')->name('own-cars.storeMedia');
    Route::post('own-cars/ckmedia', 'OwnCarController@storeCKEditorImages')->name('own-cars.storeCKEditorImages');
    Route::resource('own-cars', 'OwnCarController');

    // Own Car Form
    Route::delete('own-car-forms/destroy', 'OwnCarFormController@massDestroy')->name('own-car-forms.massDestroy');
    Route::resource('own-car-forms', 'OwnCarFormController');

    // Fuel
    Route::delete('fuels/destroy', 'FuelController@massDestroy')->name('fuels.massDestroy');
    Route::resource('fuels', 'FuelController');

    // Month
    Route::delete('months/destroy', 'MonthController@massDestroy')->name('months.massDestroy');
    Route::resource('months', 'MonthController');

    // Origin
    Route::delete('origins/destroy', 'OriginController@massDestroy')->name('origins.massDestroy');
    Route::resource('origins', 'OriginController');

    // Stand Car
    Route::delete('stand-cars/destroy', 'StandCarController@massDestroy')->name('stand-cars.massDestroy');
    Route::post('stand-cars/media', 'StandCarController@storeMedia')->name('stand-cars.storeMedia');
    Route::post('stand-cars/ckmedia', 'StandCarController@storeCKEditorImages')->name('stand-cars.storeCKEditorImages');
    Route::resource('stand-cars', 'StandCarController');

    // Stand Car Form
    Route::delete('stand-car-forms/destroy', 'StandCarFormController@massDestroy')->name('stand-car-forms.massDestroy');
    Route::resource('stand-car-forms', 'StandCarFormController');

    // Status
    Route::delete('statuses/destroy', 'StatusController@massDestroy')->name('statuses.massDestroy');
    Route::resource('statuses', 'StatusController');

    // Courier
    Route::delete('couriers/destroy', 'CourierController@massDestroy')->name('couriers.massDestroy');
    Route::post('couriers/media', 'CourierController@storeMedia')->name('couriers.storeMedia');
    Route::post('couriers/ckmedia', 'CourierController@storeCKEditorImages')->name('couriers.storeCKEditorImages');
    Route::resource('couriers', 'CourierController');

    // Courier Form
    Route::delete('courier-forms/destroy', 'CourierFormController@massDestroy')->name('courier-forms.massDestroy');
    Route::resource('courier-forms', 'CourierFormController');

    // Training
    Route::delete('trainings/destroy', 'TrainingController@massDestroy')->name('trainings.massDestroy');
    Route::post('trainings/media', 'TrainingController@storeMedia')->name('trainings.storeMedia');
    Route::post('trainings/ckmedia', 'TrainingController@storeCKEditorImages')->name('trainings.storeCKEditorImages');
    Route::resource('trainings', 'TrainingController');

    // Training Form
    Route::delete('training-forms/destroy', 'TrainingFormController@massDestroy')->name('training-forms.massDestroy');
    Route::resource('training-forms', 'TrainingFormController');

    // Product Category
    Route::delete('product-categories/destroy', 'ProductCategoryController@massDestroy')->name('product-categories.massDestroy');
    Route::post('product-categories/media', 'ProductCategoryController@storeMedia')->name('product-categories.storeMedia');
    Route::post('product-categories/ckmedia', 'ProductCategoryController@storeCKEditorImages')->name('product-categories.storeCKEditorImages');
    Route::resource('product-categories', 'ProductCategoryController');

    // Product Tag
    Route::delete('product-tags/destroy', 'ProductTagController@massDestroy')->name('product-tags.massDestroy');
    Route::resource('product-tags', 'ProductTagController');

    // Product
    Route::delete('products/destroy', 'ProductController@massDestroy')->name('products.massDestroy');
    Route::post('products/media', 'ProductController@storeMedia')->name('products.storeMedia');
    Route::post('products/ckmedia', 'ProductController@storeCKEditorImages')->name('products.storeCKEditorImages');
    Route::resource('products', 'ProductController');

    // Product Form
    Route::delete('product-forms/destroy', 'ProductFormController@massDestroy')->name('product-forms.massDestroy');
    Route::resource('product-forms', 'ProductFormController');

    // Consulting
    Route::delete('consultings/destroy', 'ConsultingController@massDestroy')->name('consultings.massDestroy');
    Route::post('consultings/media', 'ConsultingController@storeMedia')->name('consultings.storeMedia');
    Route::post('consultings/ckmedia', 'ConsultingController@storeCKEditorImages')->name('consultings.storeCKEditorImages');
    Route::resource('consultings', 'ConsultingController');

    // Consulting Form
    Route::delete('consulting-forms/destroy', 'ConsultingFormController@massDestroy')->name('consulting-forms.massDestroy');
    Route::resource('consulting-forms', 'ConsultingFormController');

    // Newsletter
    Route::delete('newsletters/destroy', 'NewsletterController@massDestroy')->name('newsletters.massDestroy');
    Route::post('newsletters/parse-csv-import', 'NewsletterController@parseCsvImport')->name('newsletters.parseCsvImport');
    Route::post('newsletters/process-csv-import', 'NewsletterController@processCsvImport')->name('newsletters.processCsvImport');
    Route::resource('newsletters', 'NewsletterController');

    // Brand
    Route::delete('brands/destroy', 'BrandController@massDestroy')->name('brands.massDestroy');
    Route::resource('brands', 'BrandController');

    // Car Model
    Route::delete('car-models/destroy', 'CarModelController@massDestroy')->name('car-models.massDestroy');
    Route::resource('car-models', 'CarModelController');

    // Page
    Route::delete('pages/destroy', 'PageController@massDestroy')->name('pages.massDestroy');
    Route::post('pages/media', 'PageController@storeMedia')->name('pages.storeMedia');
    Route::post('pages/ckmedia', 'PageController@storeCKEditorImages')->name('pages.storeCKEditorImages');
    Route::resource('pages', 'PageController');

    // Legal
    Route::delete('legals/destroy', 'LegalController@massDestroy')->name('legals.massDestroy');
    Route::post('legals/media', 'LegalController@storeMedia')->name('legals.storeMedia');
    Route::post('legals/ckmedia', 'LegalController@storeCKEditorImages')->name('legals.storeCKEditorImages');
    Route::resource('legals', 'LegalController');

    // Page Form
    Route::delete('page-forms/destroy', 'PageFormController@massDestroy')->name('page-forms.massDestroy');
    Route::resource('page-forms', 'PageFormController');

    // Transfer Form
    Route::delete('transfer-forms/destroy', 'TransferFormController@massDestroy')->name('transfer-forms.massDestroy');
    Route::resource('transfer-forms', 'TransferFormController');

    // Transfer Tour
    Route::delete('transfer-tours/destroy', 'TransferTourController@massDestroy')->name('transfer-tours.massDestroy');
    Route::post('transfer-tours/media', 'TransferTourController@storeMedia')->name('transfer-tours.storeMedia');
    Route::post('transfer-tours/ckmedia', 'TransferTourController@storeCKEditorImages')->name('transfer-tours.storeCKEditorImages');
    Route::resource('transfer-tours', 'TransferTourController');

    // Driver
    Route::delete('drivers/destroy', 'DriverController@massDestroy')->name('drivers.massDestroy');
    Route::post('drivers/parse-csv-import', 'DriverController@parseCsvImport')->name('drivers.parseCsvImport');
    Route::post('drivers/process-csv-import', 'DriverController@processCsvImport')->name('drivers.processCsvImport');
    Route::resource('drivers', 'DriverController');

    // Card
    Route::delete('cards/destroy', 'CardController@massDestroy')->name('cards.massDestroy');
    Route::resource('cards', 'CardController');

    // Local
    Route::delete('locals/destroy', 'LocalController@massDestroy')->name('locals.massDestroy');
    Route::resource('locals', 'LocalController');

    // State
    Route::delete('states/destroy', 'StateController@massDestroy')->name('states.massDestroy');
    Route::resource('states', 'StateController');

    // Tvde Year
    Route::delete('tvde-years/destroy', 'TvdeYearController@massDestroy')->name('tvde-years.massDestroy');
    Route::resource('tvde-years', 'TvdeYearController');

    // Tvde Month
    Route::delete('tvde-months/destroy', 'TvdeMonthController@massDestroy')->name('tvde-months.massDestroy');
    Route::resource('tvde-months', 'TvdeMonthController');

    // Tvde Week
    Route::delete('tvde-weeks/destroy', 'TvdeWeekController@massDestroy')->name('tvde-weeks.massDestroy');
    Route::resource('tvde-weeks', 'TvdeWeekController');

    // Tvde Operator
    Route::delete('tvde-operators/destroy', 'TvdeOperatorController@massDestroy')->name('tvde-operators.massDestroy');
    Route::resource('tvde-operators', 'TvdeOperatorController');

    // Receipt
    Route::delete('receipts/destroy', 'ReceiptController@massDestroy')->name('receipts.massDestroy');
    Route::post('receipts/media', 'ReceiptController@storeMedia')->name('receipts.storeMedia');
    Route::post('receipts/ckmedia', 'ReceiptController@storeCKEditorImages')->name('receipts.storeCKEditorImages');
    Route::get('receipts/checkPay/{receipt_id}', 'ReceiptController@checkPay');
    Route::get('receipts/checkVerified/{receipt_id}/{receipt_value}/{amount_transferred}', 'ReceiptController@checkVerified');
    Route::get('receipts/paid', 'ReceiptController@index');
    Route::resource('receipts', 'ReceiptController');

    // My Receipts
    Route::prefix('my-receipts')->group(function () {
        Route::get('/{paid?}', 'MyReceiptsController@index')->name('my-receipts.index');
        Route::post('create', 'MyReceiptsController@create');
        Route::get('pay-receipt/{receipt_id}/{paid}', 'MyReceiptsController@payReceipt');
        Route::get('checkPay/{receipt_id}', 'MyReceiptsController@checkPay');
        Route::get('checkVerified/{receipt_id}/{receipt_value}/{amount_transferred}', 'MyReceiptsController@checkVerified');
    });

    // Document
    Route::delete('documents/destroy', 'DocumentController@massDestroy')->name('documents.massDestroy');
    Route::post('documents/media', 'DocumentController@storeMedia')->name('documents.storeMedia');
    Route::post('documents/ckmedia', 'DocumentController@storeCKEditorImages')->name('documents.storeCKEditorImages');
    Route::resource('documents', 'DocumentController');

    // My Document
    Route::get('my-documents', 'MyDocumentController@index')->name('my-documents.index');
    Route::post('my-documents/media', 'MyDocumentController@storeMedia')->name('my-documents.storeMedia');
    Route::post('my-documents/update/{id}', 'MyDocumentController@update')->name('my-documents.update');

    // Financial Statement
    Route::prefix('financial-statements')->group(function () {
        Route::get('/', 'FinancialStatementController@index')->name('financial-statements.index');
        Route::get('pdf/{download?}', 'FinancialStatementController@pdf');
        Route::get('year/{tvde_year_id}', 'FinancialStatementController@year');
        Route::get('month/{tvde_month_id}', 'FinancialStatementController@month');
        Route::get('week/{tvde_week_id}', 'FinancialStatementController@week');
        Route::get('driver/{driver_id}', 'FinancialStatementController@driver');
        Route::post('update-balance', 'FinancialStatementController@updateBalance');
    });

    // Company Document
    Route::delete('company-documents/destroy', 'CompanyDocumentController@massDestroy')->name('company-documents.massDestroy');
    Route::post('company-documents/media', 'CompanyDocumentController@storeMedia')->name('company-documents.storeMedia');
    Route::post('company-documents/ckmedia', 'CompanyDocumentController@storeCKEditorImages')->name('company-documents.storeCKEditorImages');
    Route::resource('company-documents', 'CompanyDocumentController');

    Route::get('system-calendar', 'SystemCalendarController@index')->name('systemCalendar');
    Route::get('messenger', 'MessengerController@index')->name('messenger.index');
    Route::get('messenger/create', 'MessengerController@createTopic')->name('messenger.createTopic');
    Route::post('messenger', 'MessengerController@storeTopic')->name('messenger.storeTopic');
    Route::get('messenger/inbox', 'MessengerController@showInbox')->name('messenger.showInbox');
    Route::get('messenger/outbox', 'MessengerController@showOutbox')->name('messenger.showOutbox');
    Route::get('messenger/{topic}', 'MessengerController@showMessages')->name('messenger.showMessages');
    Route::delete('messenger/{topic}', 'MessengerController@destroyTopic')->name('messenger.destroyTopic');
    Route::post('messenger/{topic}/reply', 'MessengerController@replyToTopic')->name('messenger.reply');
    Route::get('messenger/{topic}/reply', 'MessengerController@showReply')->name('messenger.showReply');

    // Statement Of Responsibility
    Route::prefix('statement-of-responsibilities')->group(function () {
        Route::get('/', 'StatementOfResponsibilityController@index')->name('statement-of-responsibilities.index');
        Route::get('pdf/{download?}', 'StatementOfResponsibilityController@pdf');
        Route::get('signContract', 'StatementOfResponsibilityController@signContract');
    });

    // Contract
    Route::prefix('contracts')->group(function () {
        Route::get('/', 'ContractController@index')->name('contracts.index');
        Route::get('pdf/{download?}', 'ContractController@pdf');
        Route::get('signContract', 'ContractController@signContract');
    });

    // Admin Statement Responsibility
    Route::delete('admin-statement-responsibilities/destroy', 'AdminStatementResponsibilityController@massDestroy')->name('admin-statement-responsibilities.massDestroy');
    Route::resource('admin-statement-responsibilities', 'AdminStatementResponsibilityController');

    // Admin Contract
    Route::delete('admin-contracts/destroy', 'AdminContractController@massDestroy')->name('admin-contracts.massDestroy');
    Route::resource('admin-contracts', 'AdminContractController');

    // Vehicle Brand
    Route::delete('vehicle-brands/destroy', 'VehicleBrandController@massDestroy')->name('vehicle-brands.massDestroy');
    Route::resource('vehicle-brands', 'VehicleBrandController');

    // Vehicle Model
    Route::delete('vehicle-models/destroy', 'VehicleModelController@massDestroy')->name('vehicle-models.massDestroy');
    Route::resource('vehicle-models', 'VehicleModelController');

    // Vehicle Event Type
    Route::delete('vehicle-event-types/destroy', 'VehicleEventTypeController@massDestroy')->name('vehicle-event-types.massDestroy');
    Route::resource('vehicle-event-types', 'VehicleEventTypeController');

    // Vehicle Event Warning Time
    Route::delete('vehicle-event-warning-times/destroy', 'VehicleEventWarningTimeController@massDestroy')->name('vehicle-event-warning-times.massDestroy');
    Route::resource('vehicle-event-warning-times', 'VehicleEventWarningTimeController');

    // Vehicle Event
    Route::delete('vehicle-events/destroy', 'VehicleEventController@massDestroy')->name('vehicle-events.massDestroy');
    Route::resource('vehicle-events', 'VehicleEventController');

    // Vehicle Item
    Route::delete('vehicle-items/destroy', 'VehicleItemController@massDestroy')->name('vehicle-items.massDestroy');
    Route::post('vehicle-items/media', 'VehicleItemController@storeMedia')->name('vehicle-items.storeMedia');
    Route::post('vehicle-items/ckmedia', 'VehicleItemController@storeCKEditorImages')->name('vehicle-items.storeCKEditorImages');
    Route::resource('vehicle-items', 'VehicleItemController');

    // Company
    Route::delete('companies/destroy', 'CompanyController@massDestroy')->name('companies.massDestroy');
    Route::post('companies/media', 'CompanyController@storeMedia')->name('companies.storeMedia');
    Route::post('companies/ckmedia', 'CompanyController@storeCKEditorImages')->name('companies.storeCKEditorImages');
    Route::post('companies/parse-csv-import', 'CompanyController@parseCsvImport')->name('companies.parseCsvImport');
    Route::post('companies/process-csv-import', 'CompanyController@processCsvImport')->name('companies.processCsvImport');
    Route::resource('companies', 'CompanyController');

    // Electric
    Route::delete('electrics/destroy', 'ElectricController@massDestroy')->name('electrics.massDestroy');
    Route::resource('electrics', 'ElectricController');

    // Tvde Activity
    Route::delete('tvde-activities/destroy', 'TvdeActivityController@massDestroy')->name('tvde-activities.massDestroy');
    Route::post('tvde-activities/parse-csv-import', 'TvdeActivityController@parseCsvImport')->name('tvde-activities.parseCsvImport');
    Route::post('tvde-activities/process-csv-import', 'TvdeActivityController@processCsvImport')->name('tvde-activities.processCsvImport');
    Route::resource('tvde-activities', 'TvdeActivityController');
    Route::post('tvde-activities/delete-filter', 'TvdeActivityController@deleteFilter');

    // Contract Type
    Route::delete('contract-types/destroy', 'ContractTypeController@massDestroy')->name('contract-types.massDestroy');
    Route::resource('contract-types', 'ContractTypeController');

    // Contract Type Rank
    Route::delete('contract-type-ranks/destroy', 'ContractTypeRankController@massDestroy')->name('contract-type-ranks.massDestroy');
    Route::resource('contract-type-ranks', 'ContractTypeRankController');

    // Contract Vat
    Route::delete('contract-vats/destroy', 'ContractVatController@massDestroy')->name('contract-vats.massDestroy');
    Route::resource('contract-vats', 'ContractVatController');

    // Combustion Transaction
    Route::delete('combustion-transactions/destroy', 'CombustionTransactionController@massDestroy')->name('combustion-transactions.massDestroy');
    Route::post('combustion-transactions/parse-csv-import', 'CombustionTransactionController@parseCsvImport')->name('combustion-transactions.parseCsvImport');
    Route::post('combustion-transactions/process-csv-import', 'CombustionTransactionController@processCsvImport')->name('combustion-transactions.processCsvImport');
    Route::resource('combustion-transactions', 'CombustionTransactionController');

    // Electric Transaction
    Route::delete('electric-transactions/destroy', 'ElectricTransactionController@massDestroy')->name('electric-transactions.massDestroy');
    Route::post('electric-transactions/parse-csv-import', 'ElectricTransactionController@parseCsvImport')->name('electric-transactions.parseCsvImport');
    Route::post('electric-transactions/process-csv-import', 'ElectricTransactionController@processCsvImport')->name('electric-transactions.processCsvImport');
    Route::resource('electric-transactions', 'ElectricTransactionController');

    // Adjustment
    Route::delete('adjustments/destroy', 'AdjustmentController@massDestroy')->name('adjustments.massDestroy');
    Route::post('adjustments/parse-csv-import', 'AdjustmentController@parseCsvImport')->name('adjustments.parseCsvImport');
    Route::post('adjustments/process-csv-import', 'AdjustmentController@processCsvImport')->name('adjustments.processCsvImport');
    Route::resource('adjustments', 'AdjustmentController');

    // Company Expense
    Route::delete('company-expenses/destroy', 'CompanyExpenseController@massDestroy')->name('company-expenses.massDestroy');
    Route::post('company-expenses/parse-csv-import', 'CompanyExpenseController@parseCsvImport')->name('company-expenses.parseCsvImport');
    Route::post('company-expenses/process-csv-import', 'CompanyExpenseController@processCsvImport')->name('company-expenses.processCsvImport');
    Route::resource('company-expenses', 'CompanyExpenseController');

    // Weekly Expense Report
    Route::prefix('weekly-expense-reports')->group(function () {
        Route::get('/', 'WeeklyExpenseReportController@index');
        Route::get('pdf/{download?}', 'WeeklyExpenseReportController@pdf');
        Route::get('update', 'WeeklyExpenseReportController@update');
    });

    // Company Report
    Route::prefix('company-reports')->group(function () {
        Route::get('/', 'CompanyReportController@index')->name('company-reports.index');
        Route::post('validate-data', 'CompanyReportController@validateData');
        Route::post('revalidate-data', 'CompanyReportController@revalidateData');
        Route::get('delete-data/{tvde_week_id}/{driver_id}', 'CompanyReportController@deleteData')->name('company-reports.delete-data');
        Route::get('driver-report-all-weeks/{driver_id?}/{state_id?}', 'CompanyReportController@driverReportAllWeeks')->name('company-reports.driver-report-all-weeks');
    });

    // Company Park
    Route::delete('company-parks/destroy', 'CompanyParkController@massDestroy')->name('company-parks.massDestroy');
    Route::resource('company-parks', 'CompanyParkController');

    // Consultancy
    Route::delete('consultancies/destroy', 'ConsultancyController@massDestroy')->name('consultancies.massDestroy');
    Route::resource('consultancies', 'ConsultancyController');

    // Current Account
    Route::delete('current-accounts/destroy', 'CurrentAccountController@massDestroy')->name('current-accounts.massDestroy');
    Route::resource('current-accounts', 'CurrentAccountController');

    // Drivers Balance
    Route::delete('drivers-balances/destroy', 'DriversBalanceController@massDestroy')->name('drivers-balances.massDestroy');
    Route::resource('drivers-balances', 'DriversBalanceController');

    // Toll Payment
    Route::delete('toll-payments/destroy', 'TollPaymentController@massDestroy')->name('toll-payments.massDestroy');
    Route::post('toll-payments/parse-csv-import', 'TollPaymentController@parseCsvImport')->name('toll-payments.parseCsvImport');
    Route::post('toll-payments/process-csv-import', 'TollPaymentController@processCsvImport')->name('toll-payments.processCsvImport');
    Route::resource('toll-payments', 'TollPaymentController');

    // Toll Card
    Route::delete('toll-cards/destroy', 'TollCardController@massDestroy')->name('toll-cards.massDestroy');
    Route::resource('toll-cards', 'TollCardController');

    // Team
    Route::delete('teams/destroy', 'TeamController@massDestroy')->name('teams.massDestroy');
    Route::resource('teams', 'TeamController');

    // Company Invoice
    Route::delete('company-invoices/destroy', 'CompanyInvoiceController@massDestroy')->name('company-invoices.massDestroy');
    Route::post('company-invoices/media', 'CompanyInvoiceController@storeMedia')->name('company-invoices.storeMedia');
    Route::post('company-invoices/ckmedia', 'CompanyInvoiceController@storeCKEditorImages')->name('company-invoices.storeCKEditorImages');
    Route::resource('company-invoices', 'CompanyInvoiceController');

    // Company Data
    Route::delete('company-datas/destroy', 'CompanyDataController@massDestroy')->name('company-datas.massDestroy');
    Route::resource('company-datas', 'CompanyDataController');

    // Form Name
    Route::delete('form-names/destroy', 'FormNameController@massDestroy')->name('form-names.massDestroy');
    Route::post('form-names/media', 'FormNameController@storeMedia')->name('form-names.storeMedia');
    Route::post('form-names/ckmedia', 'FormNameController@storeCKEditorImages')->name('form-names.storeCKEditorImages');
    Route::resource('form-names', 'FormNameController');

    // Form Input
    Route::delete('form-inputs/destroy', 'FormInputController@massDestroy')->name('form-inputs.massDestroy');
    Route::resource('form-inputs', 'FormInputController');

    // Form Data
    Route::delete('form-datas/destroy', 'FormDataController@massDestroy')->name('form-datas.massDestroy');
    Route::resource('form-datas', 'FormDataController');

    // Form Assembly
    Route::prefix('form-assemblies')->group(function () {
        Route::get('/{id?}', 'FormAssemblyController@index')->name('form-assemblies.index');
        Route::post('add-form-name', 'FormAssemblyController@addFormName')->name('form-assemblies.add-form-name');
        Route::post('new-field', 'FormAssemblyController@newField')->name('form-assemblies.new-field');
        Route::post('send-form-data', 'FormAssemblyController@sendFormData')->name('form-assemblies.send-form-data');
        Route::get('delete-form-name/{form_name_id}', 'FormAssemblyController@deleteFormName')->name('form-assemblies.delete-form-name');
        Route::get('delete-form-input/{form_input_id}', 'FormAssemblyController@deleteFormInput')->name('form-assemblies.delete-form-input');
        Route::get('form-inputs/{form_name_id}', 'FormAssemblyController@formInputs')->name('form-assemblies.form-inputs');
        Route::post('update-input-position', 'FormAssemblyController@updateInputPosition')->name('form-assemblies.update-input-position');
        Route::post('store-media', 'FormAssemblyController@uploadFile')->name('form-assemblies.store-media');
    });

    // Form Communication
    Route::prefix('form-communications')->group(function () {
        Route::get('/', 'FormCommunicationController@index')->name('form-communications.index');
        Route::get('form/{form_id}', 'FormCommunicationController@form');
    });

    // Registo Entrada Veiculo
    Route::delete('registo-entrada-veiculos/destroy', 'RegistoEntradaVeiculoController@massDestroy')->name('registo-entrada-veiculos.massDestroy');
    Route::post('registo-entrada-veiculos/media', 'RegistoEntradaVeiculoController@storeMedia')->name('registo-entrada-veiculos.storeMedia');
    Route::post('registo-entrada-veiculos/ckmedia', 'RegistoEntradaVeiculoController@storeCKEditorImages')->name('registo-entrada-veiculos.storeCKEditorImages');
    Route::resource('registo-entrada-veiculos', 'RegistoEntradaVeiculoController');
    Route::get('registo-entrada-veiculos/photos/{vehicle_item_id}', 'RegistoEntradaVeiculoController@photos')->name('registo-entrada-veiculos.photos');
    Route::get('registo-entrada-veiculos/delete-media/{media_id}', 'RegistoEntradaVeiculoController@deleteMedia');

    // Recruitment Form
    Route::delete('recruitment-forms/destroy', 'RecruitmentFormController@massDestroy')->name('recruitment-forms.massDestroy');
    Route::post('recruitment-forms/media', 'RecruitmentFormController@storeMedia')->name('recruitment-forms.storeMedia');
    Route::post('recruitment-forms/ckmedia', 'RecruitmentFormController@storeCKEditorImages')->name('recruitment-forms.storeCKEditorImages');
    Route::resource('recruitment-forms', 'RecruitmentFormController');

    // Weekly Vehicle Expenses
    Route::delete('weekly-vehicle-expenses/destroy', 'WeeklyVehicleExpensesController@massDestroy')->name('weekly-vehicle-expenses.massDestroy');
    Route::post('weekly-vehicle-expenses/parse-csv-import', 'WeeklyVehicleExpensesController@parseCsvImport')->name('weekly-vehicle-expenses.parseCsvImport');
    Route::post('weekly-vehicle-expenses/process-csv-import', 'WeeklyVehicleExpensesController@processCsvImport')->name('weekly-vehicle-expenses.processCsvImport');
    Route::resource('weekly-vehicle-expenses', 'WeeklyVehicleExpensesController');

    // Periods Of The Year
    Route::delete('periods-of-the-years/destroy', 'PeriodsOfTheYearController@massDestroy')->name('periods-of-the-years.massDestroy');
    Route::resource('periods-of-the-years', 'PeriodsOfTheYearController');

    // Car Track
    Route::delete('car-tracks/destroy', 'CarTrackController@massDestroy')->name('car-tracks.massDestroy');
    Route::post('car-tracks/parse-csv-import', 'CarTrackController@parseCsvImport')->name('car-tracks.parseCsvImport');
    Route::post('car-tracks/process-csv-import', 'CarTrackController@processCsvImport')->name('car-tracks.processCsvImport');
    Route::resource('car-tracks', 'CarTrackController');

    // Car Hire
    Route::delete('car-hires/destroy', 'CarHireController@massDestroy')->name('car-hires.massDestroy');
    Route::post('car-hires/parse-csv-import', 'CarHireController@parseCsvImport')->name('car-hires.parseCsvImport');
    Route::post('car-hires/process-csv-import', 'CarHireController@processCsvImport')->name('car-hires.processCsvImport');
    Route::resource('car-hires', 'CarHireController');

    // Drivers Payments
    Route::prefix('drivers-payments')->group(function () {
        Route::get('/', 'DriversPaymentsController@index')->name('drivers-payments.index');
        Route::post('create-xml', 'DriversPaymentsController@createXml');
    });

    // Vehicle Expenses
    Route::delete('vehicle-expenses/destroy', 'VehicleExpensesController@massDestroy')->name('vehicle-expenses.massDestroy');
    Route::post('vehicle-expenses/media', 'VehicleExpensesController@storeMedia')->name('vehicle-expenses.storeMedia');
    Route::post('vehicle-expenses/ckmedia', 'VehicleExpensesController@storeCKEditorImages')->name('vehicle-expenses.storeCKEditorImages');
    Route::post('vehicle-expenses/parse-csv-import', 'VehicleExpensesController@parseCsvImport')->name('vehicle-expenses.parseCsvImport');
    Route::post('vehicle-expenses/process-csv-import', 'VehicleExpensesController@processCsvImport')->name('vehicle-expenses.processCsvImport');
    Route::resource('vehicle-expenses', 'VehicleExpensesController');

    // Vehicle Profitability
    Route::prefix('vehicle-profitabilities')->group(function() {
        Route::get('/', [App\Http\Controllers\Admin\VehicleProfitabilityController::class, 'index'])
            ->name('vehicle-profitabilities.index');
        Route::get('set-vehicle-item-id/{vehicle_item_id}', [\App\Http\Controllers\Admin\VehicleProfitabilityController::class, 'setVehicleItemId']);
    });

    // Expense Receipts
    Route::delete('expense-receipts/destroy', 'ExpenseReceiptsController@massDestroy')->name('expense-receipts.massDestroy');
    Route::post('expense-receipts/media', 'ExpenseReceiptsController@storeMedia')->name('expense-receipts.storeMedia');
    Route::post('expense-receipts/ckmedia', 'ExpenseReceiptsController@storeCKEditorImages')->name('expense-receipts.storeCKEditorImages');
    Route::resource('expense-receipts', 'ExpenseReceiptsController');

    // Vehicle Usage
    Route::delete('vehicle-usages/destroy', 'VehicleUsageController@massDestroy')->name('vehicle-usages.massDestroy');
    Route::resource('vehicle-usages', 'VehicleUsageController');
    Route::get('vehicle-usage', 'VehicleUsageController@usage')->name('vehicle-usage');

    // Tesla Charging
    Route::delete('tesla-chargings/destroy', 'TeslaChargingController@massDestroy')->name('tesla-chargings.massDestroy');
    Route::post('tesla-chargings/parse-csv-import', 'TeslaChargingController@parseCsvImport')->name('tesla-chargings.parseCsvImport');
    Route::post('tesla-chargings/process-csv-import', 'TeslaChargingController@processCsvImport')->name('tesla-chargings.processCsvImport');
    Route::resource('tesla-chargings', 'TeslaChargingController');

    // Expense Reimbursement
    Route::delete('expense-reimbursements/destroy', 'ExpenseReimbursementController@massDestroy')->name('expense-reimbursements.massDestroy');
    Route::post('expense-reimbursements/parse-csv-import', 'ExpenseReimbursementController@parseCsvImport')->name('expense-reimbursements.parseCsvImport');
    Route::post('expense-reimbursements/process-csv-import', 'ExpenseReimbursementController@processCsvImport')->name('expense-reimbursements.processCsvImport');
    Route::resource('expense-reimbursements', 'ExpenseReimbursementController');

    // Reimbursement
    Route::delete('reimbursements/destroy', 'ReimbursementController@massDestroy')->name('reimbursements.massDestroy');
    Route::post('reimbursements/media', 'ReimbursementController@storeMedia')->name('reimbursements.storeMedia');
    Route::post('reimbursements/ckmedia', 'ReimbursementController@storeCKEditorImages')->name('reimbursements.storeCKEditorImages');
    Route::resource('reimbursements', 'ReimbursementController');

    // Vehicle Earnings
    Route::get('vehicle-earnings', 'VehicleEarningsController@index')->name('vehicle-earnings.index');

});

Route::group(['prefix' => 'profile', 'as' => 'profile.', 'namespace' => 'Auth', 'middleware' => ['auth']], function () {
    // Change password
    if (file_exists(app_path('Http/Controllers/Auth/ChangePasswordController.php'))) {
        Route::get('password', 'ChangePasswordController@edit')->name('password.edit');
        Route::post('password', 'ChangePasswordController@update')->name('password.update');
        Route::post('profile', 'ChangePasswordController@updateProfile')->name('password.updateProfile');
        Route::post('profile/destroy', 'ChangePasswordController@destroy')->name('password.destroyProfile');
    }
});
