- [ ] Inspect where `ALTER TABLE users ADD COLUMN created_at` / other columns are executed in admin_user_management.php
- [ ] Patch those ALTERs to be conditional (only add if column doesn’t exist)
- [ ] Ensure page rendering still works (SELECT includes created_at)
- [ ] Refresh admin_user_management.php to confirm fatal error is gone

