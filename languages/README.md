# Typeform Quizzes - Translation Files

This directory contains translation files for the Typeform Quizzes WordPress plugin.

## Files

- `typeform-quizzes.pot` - Template file containing all translatable strings
- `typeform-quizzes-es_ES.po` - Sample Spanish translation (incomplete)

## How to Translate

1. **Copy the template file**: Copy `typeform-quizzes.pot` to create a new translation file for your language
2. **Rename the file**: Use the format `typeform-quizzes-{locale}.po` (e.g., `typeform-quizzes-fr_FR.po` for French)
3. **Edit the header**: Update the header information in your translation file:
   - `Last-Translator`: Your name and email
   - `Language-Team`: Your language team
   - `PO-Revision-Date`: Current date
4. **Translate the strings**: For each `msgid` entry, provide the translation in the `msgstr` field
5. **Compile the translation**: Use `msgfmt` to compile the `.po` file into a `.mo` file:
   ```bash
   msgfmt typeform-quizzes-{locale}.po -o typeform-quizzes-{locale}.mo
   ```

## Translation Guidelines

- Keep the same formatting and HTML tags as the original
- Maintain the same tone and style
- Test your translations in the WordPress admin area
- Use proper WordPress terminology when available
- Be consistent with terminology throughout the file

## Supported Languages

The plugin supports all languages that WordPress supports. The text domain is `typeform-quizzes`.

## Contributing Translations

If you would like to contribute a translation, please:

1. Complete the translation file
2. Test it thoroughly in a WordPress installation
3. Submit it as a pull request or contact the plugin author

## Technical Details

- **Text Domain**: `typeform-quizzes`
- **Domain Path**: `/languages`
- **Total Strings**: 114 translatable strings
- **Last Updated**: September 10, 2025

For questions about translations, please contact the plugin author or create an issue on the plugin's support forum.
