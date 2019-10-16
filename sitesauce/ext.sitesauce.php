<?php

class Sitesauce_ext
{
    var $name = 'Sitesauce';
    var $version = '1.0';
    var $description = 'Connect your ExpressionEngine site with Sitesauce to keep your static site updated.';
    var $settings_exist = 'y';
    var $docs_url = '';

    public $settings = [];

    public static $entryId = null;

    public function __construct($settings = [])
    {
        $this->settings = $settings;
    }

    public function activate_extension()
    {
        file_put_contents(__DIR__.'/../../language/english/sitesauce_lang.php', '');

        $this->settings = [
            'build_hook'   => null,
        ];

        ee()->db->insert('extensions', [
            'class'     => __CLASS__,
            'method'    => 'call_build_hook',
            'hook'      => 'after_channel_entry_save',
            'settings'  => serialize($this->settings),
            'priority'  => 10,
            'version'   => $this->version,
            'enabled'   => 'y'
        ]);
    }

    function settings_form($current)
    {
        if ($current == '') {
            $current = [];
        }

        $vars = [
            'base_url' => ee('CP/URL')->make('addons/settings/sitesauce/save'),
            'cp_page_title' => 'Sitesauce',
            'save_btn_text' => 'btn_save_settings',
            'save_btn_text_working' => 'btn_saving',
            'alerts_name' => 'sitesauce-save',
            'sections' => [ [ [
                'title' => 'Build Hook',
                'desc' => 'A build hook for the site you want to keep updated. You can get this value on the settings page of your site.',
                'fields' => [ 'build_hook' => [
                        'type' => 'text',
                        'value' => $current['build_hook'],
                        'required' => true,
                        'placeholder' => 'https://app.sitesauce.app/api/build_hooks/...'
                ] ]
            ] ] ]
        ];

        return ee('View')->make('sitesauce:index')->render($vars);
    }

    public function save_settings()
    {
        if (empty($_POST)) {
            show_error(lang('unauthorized_access'));
        }

        $hook = ee()->input->post('build_hook');

        if (! strpos(parse_url($hook, PHP_URL_HOST), 'sitesauce') !== false) {
            ee('CP/Alert')->makeInline('sitesauce-save')
                ->asIssue()
                ->withTitle('Whoops! Something went wrong')
                ->addToBody(sprintf('Please enter a valid build hook.', $hook))
                ->defer();
        } else {
            $this->settings['build_hook'] = $hook;

            ee()->db->update('extensions', ['settings' => serialize($this->settings)], ['class' => __CLASS__]);

            ee('CP/Alert')->makeInline('sitesauce-save')
                ->asSuccess()
                ->withTitle('Saved!')
                ->addToBody("Your build hook has been saved successfully.")
                ->defer();
        }

        ee()->functions->redirect(ee('CP/URL')->make('addons/settings/sitesauce'));
    }

    public function call_build_hook($entry, $values)
    {
        if (is_null(static::$entryId) && ! is_null($this->settings['build_hook'])) {
            static::$entryId = $values['entry_id'];

            file_get_contents($this->settings['build_hook']);
        }
    }
}
