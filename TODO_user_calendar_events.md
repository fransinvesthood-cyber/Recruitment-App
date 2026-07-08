# Calendar Events CRUD - Implementation Checklist

- [ ] Create `save_event.php` (insert/update with prepared statements)
- [ ] Create `fetch_events.php` (return JSON events for FullCalendar)
- [ ] Create `delete_event.php` (delete with prepared statements)
- [ ] Update `calendar.php`:
  - [ ] Add Bootstrap 5 modal markup (create/edit/details)
  - [ ] Add FullCalendar `dateClick` handler to open create modal + prefill date
  - [ ] Add FullCalendar `eventClick` handler to open details/edit modal
  - [ ] Add client-side validation for required fields
  - [ ] Add AJAX save handler (POST to save_event.php)
  - [ ] Add AJAX delete handler (POST to delete_event.php)
  - [ ] Refresh events dynamically after save/delete (via fetch_events.php)
- [ ] Smoke test create/edit/delete + event rendering
- [ ] Ensure security: session-based `created_by` + prepared statements

