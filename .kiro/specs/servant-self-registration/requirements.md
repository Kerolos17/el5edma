# Requirements Document

## Introduction

نظام التسجيل الذاتي للخدام (Servant Self-Registration) هو feature جديد يسمح للخدام بالتسجيل في النظام بشكل مستقل من خلال رابط عام، بدلاً من الإضافة اليدوية من قبل المسؤولين. بعد إدخال بياناتهم الأساسية، يتم إنشاء حساب تلقائياً بصلاحيات "خادم" (servant role) ويتم ربطهم بمجموعة خدمة محددة.

## Glossary

- **Registration_System**: نظام التسجيل الذاتي للخدام
- **Registration_Link**: الرابط العام الذي يستخدمه الخدام للتسجيل
- **Registration_Form**: نموذج إدخال البيانات للتسجيل
- **Servant_Account**: حساب المستخدم بصلاحيات "servant"
- **Service_Group**: مجموعة الخدمة (الأسرة) التي سينتمي إليها الخادم
- **Admin_Panel**: لوحة التحكم الخاصة بالمسؤولين (Filament)
- **Registration_Token**: رمز فريد مرتبط بمجموعة خدمة معينة يستخدم في الرابط
- **Pending_Registration**: تسجيل معلق ينتظر الموافقة
- **Auto_Approval**: الموافقة التلقائية على التسجيل
- **Manual_Approval**: الموافقة اليدوية على التسجيل من قبل المسؤول

## Requirements

### Requirement 1: إنشاء رابط تسجيل لمجموعة خدمة

**User Story:** As a service leader or family leader, I want to generate a unique registration link for my service group, so that servants can register themselves.

#### Acceptance Criteria

1. WHEN a service leader or family leader requests a registration link, THE Registration_System SHALL generate a unique Registration_Token for the Service_Group
2. THE Registration_System SHALL create a Registration_Link containing the Registration_Token
3. THE Registration_System SHALL display the Registration_Link to the authorized user
4. THE Registration_System SHALL allow copying the Registration_Link to clipboard
5. WHERE a Service_Group already has an active Registration_Token, THE Registration_System SHALL reuse the existing token
6. THE Registration_System SHALL allow regenerating a new Registration_Token to invalidate the previous link

### Requirement 2: عرض نموذج التسجيل

**User Story:** As a potential servant, I want to access a registration form through a link, so that I can register my information.

#### Acceptance Criteria

1. WHEN a user accesses a Registration_Link with a valid Registration_Token, THE Registration_System SHALL display the Registration_Form
2. THE Registration_Form SHALL include fields for name, email, phone, and password
3. THE Registration_Form SHALL display the Service_Group name associated with the Registration_Token
4. IF the Registration_Token is invalid or expired, THEN THE Registration_System SHALL display an error message
5. THE Registration_Form SHALL be accessible without authentication
6. THE Registration_Form SHALL use Arabic as the primary language with English fallback

### Requirement 3: التحقق من صحة البيانات المدخلة

**User Story:** As the system, I want to validate registration data, so that only valid information is accepted.

#### Acceptance Criteria

1. WHEN a user submits the Registration_Form, THE Registration_System SHALL validate that all required fields are filled
2. THE Registration_System SHALL validate that the email format is correct
3. THE Registration_System SHALL validate that the email is unique in the system
4. THE Registration_System SHALL validate that the phone number format is valid
5. THE Registration_System SHALL validate that the phone number is unique in the system
6. THE Registration_System SHALL validate that the password meets minimum security requirements (minimum 8 characters)
7. IF any validation fails, THEN THE Registration_System SHALL display specific error messages for each field

### Requirement 4: إنشاء حساب الخادم تلقائياً

**User Story:** As a potential servant, I want my account to be created automatically after registration, so that I can access the system immediately.

#### Acceptance Criteria

1. WHEN the Registration_Form is submitted with valid data, THE Registration_System SHALL create a new Servant_Account
2. THE Registration_System SHALL assign the "servant" role to the new account
3. THE Registration_System SHALL link the Servant_Account to the Service_Group associated with the Registration_Token
4. THE Registration_System SHALL set the account status to active (is_active = true)
5. THE Registration_System SHALL hash the password securely before storage
6. THE Registration_System SHALL set the locale to Arabic (ar) by default
7. THE Registration_System SHALL generate a unique personal_code for the servant

### Requirement 5: إشعار المسؤولين بالتسجيل الجديد

**User Story:** As a service leader or family leader, I want to be notified when a new servant registers, so that I can be aware of new team members.

#### Acceptance Criteria

1. WHEN a new Servant_Account is created through self-registration, THE Registration_System SHALL create a notification for the Service_Group leader
2. WHERE the Service_Group has a service_leader assigned, THE Registration_System SHALL create a notification for the service_leader
3. THE Registration_System SHALL include the servant name and registration timestamp in the notification
4. THE Registration_System SHALL send push notifications (FCM) to leaders who have FCM tokens registered
5. THE Registration_System SHALL insert notifications into the ministry_notifications table

### Requirement 6: تسجيل دخول الخادم بعد التسجيل

**User Story:** As a newly registered servant, I want to log in to the system, so that I can start accessing beneficiary information.

#### Acceptance Criteria

1. WHEN a Servant_Account is created successfully, THE Registration_System SHALL display a success message
2. THE Registration_System SHALL redirect the user to the login page
3. THE Registration_System SHALL allow the servant to log in using their email and password
4. WHEN the servant logs in successfully, THE Registration_System SHALL redirect them to the Admin_Panel dashboard
5. THE Registration_System SHALL record the last_login_at timestamp

### Requirement 7: إدارة روابط التسجيل في لوحة التحكم

**User Story:** As a service leader or family leader, I want to manage registration links, so that I can control access to my service group.

#### Acceptance Criteria

1. THE Admin_Panel SHALL display the current Registration_Link for the user's Service_Group
2. THE Admin_Panel SHALL provide a button to copy the Registration_Link
3. THE Admin_Panel SHALL provide a button to regenerate the Registration_Token
4. WHEN a Registration_Token is regenerated, THE Registration_System SHALL invalidate the previous token
5. THE Admin_Panel SHALL display the number of servants who registered through the link
6. WHERE the user is a super_admin or service_leader, THE Admin_Panel SHALL allow viewing registration links for all Service_Groups

### Requirement 8: تسجيل عمليات التسجيل الذاتي

**User Story:** As a system administrator, I want to track self-registrations, so that I can audit and monitor the registration process.

#### Acceptance Criteria

1. WHEN a new Servant_Account is created through self-registration, THE Registration_System SHALL create an audit log entry
2. THE Registration_System SHALL record the Registration_Token used for registration
3. THE Registration_System SHALL record the IP address of the registration request
4. THE Registration_System SHALL record the timestamp of registration
5. THE Registration_System SHALL mark the audit log entry with action type "servant_self_registered"

### Requirement 9: منع التسجيل المكرر

**User Story:** As the system, I want to prevent duplicate registrations, so that users cannot create multiple accounts with the same information.

#### Acceptance Criteria

1. WHEN a user attempts to register with an email that already exists, THE Registration_System SHALL reject the registration
2. WHEN a user attempts to register with a phone number that already exists, THE Registration_System SHALL reject the registration
3. THE Registration_System SHALL display a clear error message indicating the duplicate field
4. THE Registration_System SHALL suggest logging in if an account already exists

### Requirement 10: أمان روابط التسجيل

**User Story:** As a system administrator, I want registration links to be secure, so that unauthorized users cannot abuse the registration system.

#### Acceptance Criteria

1. THE Registration_System SHALL generate Registration_Tokens using cryptographically secure random generation
2. THE Registration_Token SHALL be at least 32 characters long
3. THE Registration_System SHALL validate the Registration_Token on every registration attempt
4. THE Registration_System SHALL implement rate limiting on registration attempts (maximum 5 attempts per IP per hour)
5. IF rate limit is exceeded, THEN THE Registration_System SHALL display an error message and block further attempts temporarily
6. THE Registration_System SHALL protect against CSRF attacks on the Registration_Form
