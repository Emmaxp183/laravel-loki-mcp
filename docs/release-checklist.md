# Release Checklist

- [ ] `composer install`
- [ ] `vendor/bin/phpunit`
- [ ] `php artisan mcp:install`
- [ ] verify `Codex CLI` snippet output
- [ ] verify `Claude Code` snippet output
- [ ] verify `config/mcp.php` is published
- [ ] verify shared-token HTTP mode with `Authorization: Bearer <token>`
- [ ] verify Passport OAuth metadata routes if `laravel/passport` is installed
- [ ] verify `laravel-files-write` works in `local` with the default config
- [ ] verify `laravel-files-write` is denied when `allow_code_edits=false` or the environment is not write-enabled
- [ ] verify blocked paths like `.env` are rejected
- [ ] verify audit log entries are written
