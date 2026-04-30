# 2FA Implementation Plan

## Status: IN PROGRESS

## Information Gathered:
- System uses SQLite database with PDO
- 2FA already uses Google2FA library (pragmarx/google2fa)
- Users table already has: `two_factor_secret`, `two_factor_enabled`
- Login happens in `login.php`
- User management in `user_management.php`
- User settings in `user_settings.php`

## Plan:

### Step 1: Database Schema Updates
- Add `two_factor_remember_token` - token for 30-day remember
- Add `two_factor_remember_expires` - expiry datetime for remember
- Add `two_factor_mandatory` - admin can force 2FA per user
- Add `system_2fa_mandatory` - global system setting table
- Add `two_factor_last_verified` - track last 2FA use (for 30-day renewal)

### Step 2: Create 2FA Helper Functions
- Create `includes/2fa_functions.php` for reusable 2FA logic

### Step 3: Modify Login (login.php)
- After password verification, check if 2FA is required
- Show 2FA input field if required
- Add "30 Tage speichern" checkbox
- Check valid remember token before skipping 2FA
- Track 2FA verification time

### Step 4: Admin 2FA Controls (user_management.php)
- Add global toggle: "2FA für alle Benutzer erzwingen"
- Show per-user 2FA status
- Allow admin to force 2FA per user

### Step 5: Update User Settings (user_settings.php)
- Show if 2FA is mandatory (admin enforced)
- Disable 2FA disable button if mandatory
- Show 2FA status and last verified date

## Files to be Modified:
1. `db_connection.php` - Add new database columns
2. `login.php` - Add 2FA verification step
3. `user_management.php` - Add admin 2FA controls
4. `user_settings.php` - Update for mandatory 2FA display
5. `includes/2fa_functions.php` - Create new helper file

