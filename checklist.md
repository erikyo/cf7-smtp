### Update checklist

- [ ] Update changelog
- [ ] Update [README.txt](README.txt) version (Tested up to -> last wordpress version)
- [ ] Update the plugin version ([README.txt](README.txt) - Stable tag)
- [ ] Update [cf7-smtp.php](cf7-smtp.php) the plugin version (plugin main file)
- [ ] Update CF7_SMTP_VERSION constant (just below)
- [ ] Update [package.json](package.json) version
- [ ] Update [composer.json](composer.json) version
- [ ] run `composer install --no-dev`
- [ ] run `composer dump-autoload --optimize`
- [ ] run `npm run plugin-zip`

🎉 🎉 🎉
