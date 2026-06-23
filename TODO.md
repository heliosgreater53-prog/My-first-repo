# TODO - Theme System Global Personalization

## Completed

- [x] Added theme registry/provider JS: `public/assets/js/theme-provider.js`
- [x] Integrated ThemeProvider into global header: `app/views/partials/header.php`
- [x] Added `/settings/themes` routes in `routes/web.php`
- [x] Implemented Themes page view: `app/views/profile/themes.php`
- [x] Extended theme selection dropdown + added “Themes” link on `/settings`: `app/views/profile/settings.php`
- [x] Implemented theme page controller endpoints: `app/controllers/SettingsController.php`
- [x] Removed legacy theme toggle fighting ThemeProvider

## Remaining (should be done to fully meet requirements)

- [ ] Add theme card/grid styling in `public/assets/css/styles.css` (or new css) so `/settings/themes` looks polished.
- [ ] Refactor remaining hardcoded colors in `public/assets/css/styles.css` to use CSS variables/tokens.
- [ ] Ensure ThemeProvider transition is subtle and respects reduce-motion.
- [ ] Validate persistence behavior end-to-end:
  - [ ] Click theme applies instantly
  - [ ] POST `/settings/themes` persists to `users.theme_preference`
  - [ ] Reload restores theme
