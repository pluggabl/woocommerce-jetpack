"""Static/source and gettext-tool regression checks; not WordPress runtime proof."""
import copy
import gettext
import importlib.util
import io
import json
from pathlib import Path
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]
spec = importlib.util.spec_from_file_location('wcj_catalog', ROOT / 'tools/i18n/catalog.py')
catalog = importlib.util.module_from_spec(spec)
spec.loader.exec_module(catalog)
spec = importlib.util.spec_from_file_location('wcj_extract', ROOT / 'tools/i18n/extract.py')
extract = importlib.util.module_from_spec(spec)
spec.loader.exec_module(extract)


class CatalogTests(unittest.TestCase):
    def setUp(self):
        self.header = {'msgid': '', 'msgstr': 'Language: nl_NL\nContent-Type: text/plain; charset=UTF-8\nPlural-Forms: nplurals=2; plural=(n != 1);\n'}
        self.entries = [self.header, {'msgid': '%1$s: %2$02d', 'msgstr': '%1$s: %2$02d'}, {'msgid': '%d day', 'msgid_plural': '%d days', 'msgstr[0]': '%d dag', 'msgstr[1]': '%d dagen'}, {'msgctxt': 'Reset filters', 'msgid': 'Clear', 'msgstr': 'Filters wissen'}, {'msgctxt': 'Clear floated form-field layout', 'msgid': 'Clear', 'msgstr': 'Nieuwe regel na veld'}]
        self.source = copy.deepcopy(self.entries)

    def test_valid_and_gettext_roundtrip(self):
        errors, translated = catalog.validate(self.entries, self.source, True)
        self.assertEqual([], errors)
        runtime = gettext.GNUTranslations(io.BytesIO(catalog.compile_mo(self.entries)))
        self.assertEqual('Filters wissen', runtime.pgettext('Reset filters', 'Clear'))
        self.assertEqual('Nieuwe regel na veld', runtime.pgettext('Clear floated form-field layout', 'Clear'))
        self.assertEqual('Clear', runtime.gettext('Clear'))
        for number in [0, 1, 2, 10, 101]:
            self.assertEqual('%d dag' if number == 1 else '%d dagen', runtime.ngettext('%d day', '%d days', number))

    def test_placeholder_width_and_type_rejected(self):
        self.entries[1]['msgstr'] = '%1$s: %2$s'
        self.assertTrue(catalog.validate(self.entries, self.source)[0])

    def test_missing_plural_branch_rejected(self):
        del self.entries[2]['msgstr[1]']
        self.assertTrue(catalog.validate(self.entries, self.source)[0])

    def test_context_collision_rejected(self):
        self.entries[4]['msgctxt'] = 'Reset filters'
        self.assertTrue(catalog.validate(self.entries, self.source)[0])

    def test_source_drift_rejected(self):
        self.entries[1]['msgid'] = 'Different source %1$s: %2$02d'
        self.assertTrue(catalog.validate(self.entries, self.source)[0])

    def test_markup_and_protected_shortcode_rejected(self):
        entry = {'msgid': '<strong>Code</strong> [wcj_order_checkout_field field_id="billing_wcj_checkout_field_1"]', 'msgstr': '<b>Code</b> [wcj_order_checkout_field meta_key="billing_wcj_checkout_field_1"]'}
        errors, _ = catalog.validate([self.header, entry], [entry])
        self.assertEqual(2, len(errors))

    def test_fuzzy_parser_rejected(self):
        with tempfile.TemporaryDirectory() as temp:
            path = Path(temp) / 'fuzzy.po'
            path.write_text('#, fuzzy, php-format\nmsgid "Clear"\nmsgstr "Wissen"\n', 'utf8')
            entry = catalog.read_po(path)[0]
            self.assertTrue(entry['fuzzy'])
            self.assertTrue(catalog.validate([self.header, entry], [entry])[0])

    def test_untranslated_is_explicit_fallback_not_compiled(self):
        self.entries[1]['msgstr'] = ''
        errors, translated = catalog.validate(self.entries, self.source)
        self.assertEqual([], errors)
        self.assertEqual(3, len(translated))
        self.assertTrue(catalog.validate(self.entries, self.source, True)[0])

    def test_locale_variants_not_silently_accepted(self):
        self.header['msgstr'] = self.header['msgstr'].replace('nl_NL', 'nl_NL_formal')
        self.assertTrue(catalog.validate(self.entries, self.source)[0])


class SourceTests(unittest.TestCase):
    def test_edition_bootstrap_uses_its_own_plugin_file_constant(self):
        constant = 'WCJ_FREE_PLUGIN_FILE' if (ROOT / 'woocommerce-jetpack.php').is_file() else 'WCJ_PLUGIN_FILE'
        loader = (ROOT / 'includes/core/wcj-loader.php').read_text('utf8')
        self.assertIn('new WCJ_Localization( ' + constant + ' );', loader)

    def test_clear_has_no_global_ambiguous_key(self):
        files = {'includes/class-wcj-order-health-admin.php': 'Reset filters', 'includes/tools/class-wcj-order-statuses-tool.php': 'Reset form', 'includes/settings/wcj-settings-checkout-custom-fields.php': 'Clear floated form-field layout', 'includes/settings/wcj-settings-eu-vat-number.php': 'Clear floated form-field layout'}
        for relative, context in files.items():
            text = (ROOT / relative).read_text('utf8')
            self.assertIn("'Clear', '" + context + "', 'woocommerce-jetpack'", text)
            self.assertNotIn("__( 'Clear', 'woocommerce-jetpack' )", text)

    def test_shortcode_help_uses_real_handler_attribute(self):
        text = (ROOT / 'includes/class-wcj-checkout-custom-fields.php').read_text('utf8')
        section = text[text.index('$this->extra_desc'):text.index('$this->wcj_checkout_custom_fields_total_number =')]
        self.assertNotIn('meta_key', section)
        self.assertNotIn('Product Input Fields value', section)
        self.assertEqual(2, section.count('[wcj_order_checkout_field field_id="billing_wcj_checkout_field_1"]'))
        handler = (ROOT / 'includes/shortcodes/class-wcj-orders-shortcodes.php').read_text('utf8')
        self.assertIn("$field_id = (string) $atts['field_id'];", handler)

    def test_locale_helper_does_not_own_translation_engine(self):
        text = (ROOT / 'includes/core/class-wcj-localization.php').read_text('utf8')
        for forbidden in ['WP_Translation_Controller', '$l10n', 'remove_filter(', 'remove_all_filters(', 'file_put_contents(', 'update_option(', '_load_textdomain_just_in_time(']:
            self.assertNotIn(forbidden, text)
        self.assertIn("get_translations_for_domain( 'woocommerce-jetpack' );", text)
        self.assertIn("load_textdomain( 'woocommerce-jetpack', $catalog, $locale );", text)
        self.assertIn("add_action( 'init', array( $this, 'load' ), 9, 0 );", text)


class ExtractionTests(unittest.TestCase):
    def test_runtime_tools_are_not_wp_cli_excluded(self):
        self.assertNotIn('tools', extract.EXCLUDE)
        self.assertIn('tools/i18n', extract.EXCLUDE)

    def test_host_scope_retains_runtime_tools_and_exact_bytes(self):
        with tempfile.TemporaryDirectory() as temp:
            root = Path(temp)
            for relative in ['includes/tools/a.php', 'tools/i18n/b.php', 'tests/test.php', 'includes/lib/c.php']:
                p = root / relative
                p.parent.mkdir(parents=True, exist_ok=True)
                p.write_bytes(b'<?php \r\n')
            files = extract.collect_files(root)
            self.assertEqual(['includes/tools/a.php'], [f['path'] for f in files])
            import hashlib
            self.assertEqual(hashlib.sha256(b'<?php \r\n').hexdigest(), files[0]['sha256'])

    def test_manifest_rejects_duplicate_and_unsafe_paths(self):
        with tempfile.TemporaryDirectory() as temp:
            p = Path(temp) / 'manifest.json'
            entry = {'path': 'includes/a.php', 'sha256': 'a' * 64}
            p.write_text(json.dumps([entry, entry]), encoding='utf-8')
            with self.assertRaises(ValueError):
                extract.manifest_files(p)
            p.write_text(json.dumps([{'path': '../outside.php', 'sha256': 'a' * 64}]), encoding='utf-8')
            with self.assertRaises(ValueError):
                extract.manifest_files(p)

    def test_manifest_uses_host_snapshot_schema(self):
        with tempfile.TemporaryDirectory() as temp:
            p = Path(temp) / 'manifest.json'
            files = [{'path': 'includes/a.php', 'sha256': 'a' * 64}]
            p.write_text(json.dumps({'source_files': files}), encoding='utf-8')
            self.assertEqual(files, extract.manifest_files(p))


if __name__ == '__main__':
    unittest.main(verbosity=2)
