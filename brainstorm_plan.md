## Brainstormed plan (snapshot reliability)

### Observed behavior
- `submit_application.php` creates `application_snapshots.profile_data` as **JSON**.
- `view_applicant_profile.php` attempts to render from `snapshot_profile` but uses inconsistent logic:
  - Personal info uses snapshot keys like `fullname`, `gender`, etc.
  - Education parses `snapshot_profile['education']` using regex or `explode('|', ...)`.
  - Work experience parses `snapshot_profile['work_experience']` using regex that assumes specific formatting.
  - Languages/computer skills have additional snapshot fallbacks.
- `my_profile.php` saves to normalized tables (`work_experience`, `qualifications`, etc.) and does **not** populate `application_snapshots`; therefore snapshot formatting must be compatible with the parsing/display.

### Root cause candidates
1. **Snapshot JSON fields don’t match parsing assumptions**
   - `submit_application.php` stores education/work experience as **strings** (`GROUP_CONCAT`), not structured arrays.
   - `view_applicant_profile.php` has regex patterns that likely don’t match the exact string format generated.

2. **Code path duplication / inconsistent rendering**
   - In the file, some blocks still refer to `$snapshot_profile['education']` but elsewhere treat snapshot profile as if it contained parsed arrays.

### Chosen approach
Keep the current snapshot string format for now (minimal DB change), but make parsing robust:
- Centralize parsing of `education` and `work_experience` strings into dedicated helper functions.
- Ensure parsing matches the exact `GROUP_CONCAT` formatting used in `submit_application.php`:
  - Education string format: `QualificationName (Institution, Year) | QualificationName (...)`
  - Work string format: `Position @ Company (duration): duties... | Position @ Company ...`

### Concrete implementation steps
1. Add small helper functions at the top of `view_applicant_profile.php`:
   - `parseEducationSnapshot(string $educationBlob): array`
   - `parseWorkExperienceSnapshot(string $workExpBlob): array`
2. Replace the current education/work parsing blocks with calls to those helpers.
3. Update parsing regex to match the known string templates:
   - Education: split by `|`, then parse `X (Y, Z)`.
   - Work: split by `|`, then parse `Position @ Company (duration): duties`.
4. Add defensive fallbacks:
   - If parsing fails for a part, include raw text rather than dropping the entry.
5. Add a sanity check debug mode (optional):
   - If `profile_data` exists but education/work parsed arrays are empty, set `$education_source` / `$work_experience_source` appropriately.

### Expected result
- Snapshot-based candidate profile pages will consistently display education and work experience.
- No schema changes required.

