# TODO - LetsChat real-time + voice + chat actions fixes

- [ ] Fix reload issue: remove `layoutMode === "room"` early-return in `php-chat-app/public/assets/js/chat.js` composer submit handler so messages send via AJAX everywhere.
- [ ] Fix reply/edit buttons triggering incorrectly: make message reply/edit handlers use event delegation (rebind-safe) after `refreshStream()` re-renders.
- [ ] Fix voice note button: implement guaranteed recording upload flow by adding backend endpoint `/chat/voice/upload`.
- [ ] Update frontend voice recording handler to upload recorded audio to `/chat/voice/upload`, then attach returned metadata/hidden fields to the composer message send.
- [ ] Update backend `ChatController@send` to accept attachment metadata from POST (not only `$_FILES`) so voice-upload works consistently.
- [ ] Add route `/chat/voice/upload` in `php-chat-app/routes/web.php`.
- [ ] Update composer partial (`php-chat-app/app/views/chat/partials/composer.php`) to include hidden fields for attachment metadata (path/type/name).
