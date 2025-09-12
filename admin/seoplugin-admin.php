<?php
/**
 * SEOPlugin Admin Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SEOPlugin_Admin {
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
        add_action( 'save_post', [ $this, 'save_meta_box' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
        add_action( 'init', [ $this, 'register_image_sizes' ] );
        add_action( 'after_setup_theme', [ $this, 'register_image_sizes' ] );
        add_action( 'wp_ajax_seoplugin_regen_og_custom', [ $this, 'regen_og_custom_callback' ] );
        add_action( 'wp_ajax_seoplugin_ai_generate_title', [ $this, 'ai_generate_title_callback' ] );
        add_action( 'wp_ajax_seoplugin_ai_generate_description', [ $this, 'ai_generate_description_callback' ] );
        add_action( 'wp_ajax_seoplugin_ai_suggest_keywords', [ $this, 'ai_suggest_keywords_callback' ] );
        add_action( 'wp_ajax_seoplugin_ai_analyze_content', [ $this, 'ai_analyze_content_callback' ] );
    }

    // Add settings page
    public function add_settings_page() {
        add_options_page(
            __( 'SEOPlugin Settings', 'seoplugin' ),
            __( 'SEOPlugin', 'seoplugin' ),
            'manage_options',
            'seoplugin-settings',
            [ $this, 'render_settings_page' ]
        );
    }

    // Register settings
    public function register_settings() {
        // General Settings
        register_setting( 'seoplugin_general_group', 'seoplugin_default_meta_description', [
            'sanitize_callback' => 'sanitize_text_field',
        ] );

        register_setting( 'seoplugin_general_group', 'seoplugin_default_meta_title', [
            'sanitize_callback' => 'sanitize_text_field',
        ] );

        register_setting( 'seoplugin_general_group', 'seoplugin_separator', [
            'sanitize_callback' => 'sanitize_text_field',
        ] );

        register_setting( 'seoplugin_general_group', 'seoplugin_homepage_title', [
            'sanitize_callback' => 'sanitize_text_field',
        ] );

        register_setting( 'seoplugin_general_group', 'seoplugin_homepage_description', [
            'sanitize_callback' => 'sanitize_textarea_field',
        ] );

        // Social Media Settings
        register_setting( 'seoplugin_social_group', 'seoplugin_facebook_app_id', [
            'sanitize_callback' => 'sanitize_text_field',
        ] );

        register_setting( 'seoplugin_social_group', 'seoplugin_twitter_username', [
            'sanitize_callback' => 'sanitize_text_field',
        ] );

        register_setting( 'seoplugin_social_group', 'seoplugin_default_og_image', [
            'sanitize_callback' => 'absint',
        ] );

        // AI Settings
        register_setting( 'seoplugin_ai_group', 'seoplugin_ai_api_key', [
            'sanitize_callback' => 'sanitize_text_field',
        ] );

        register_setting( 'seoplugin_ai_group', 'seoplugin_ai_enabled', [
            'sanitize_callback' => 'rest_sanitize_boolean',
        ] );

        // Advanced Settings
        register_setting( 'seoplugin_advanced_group', 'seoplugin_noindex_categories', [
            'sanitize_callback' => 'rest_sanitize_boolean',
        ] );

        register_setting( 'seoplugin_advanced_group', 'seoplugin_noindex_tags', [
            'sanitize_callback' => 'rest_sanitize_boolean',
        ] );

        register_setting( 'seoplugin_advanced_group', 'seoplugin_noindex_archives', [
            'sanitize_callback' => 'rest_sanitize_boolean',
        ] );

        register_setting( 'seoplugin_advanced_group', 'seoplugin_remove_category_base', [
            'sanitize_callback' => 'rest_sanitize_boolean',
        ] );

        register_setting( 'seoplugin_advanced_group', 'seoplugin_xml_sitemap_enabled', [
            'sanitize_callback' => 'rest_sanitize_boolean',
        ] );

        register_setting( 'seoplugin_advanced_group', 'seoplugin_breadcrumbs_enabled', [
            'sanitize_callback' => 'rest_sanitize_boolean',
        ] );

        // Webmaster Tools
        register_setting( 'seoplugin_webmaster_group', 'seoplugin_google_verification', [
            'sanitize_callback' => 'sanitize_text_field',
        ] );

        register_setting( 'seoplugin_webmaster_group', 'seoplugin_bing_verification', [
            'sanitize_callback' => 'sanitize_text_field',
        ] );

        register_setting( 'seoplugin_webmaster_group', 'seoplugin_yandex_verification', [
            'sanitize_callback' => 'sanitize_text_field',
        ] );

        register_setting( 'seoplugin_webmaster_group', 'seoplugin_pinterest_verification', [
            'sanitize_callback' => 'sanitize_text_field',
        ] );

        register_setting( 'seoplugin_webmaster_group', 'seoplugin_google_analytics', [
            'sanitize_callback' => 'sanitize_text_field',
        ] );

        register_setting( 'seoplugin_webmaster_group', 'seoplugin_google_tag_manager', [
            'sanitize_callback' => 'sanitize_text_field',
        ] );

        // General Settings Section
        add_settings_section(
            'seoplugin_general_section',
            __( 'General SEO Settings', 'seoplugin' ),
            null,
            'seoplugin-settings'
        );

        add_settings_field(
            'seoplugin_homepage_title',
            __( 'Homepage Title', 'seoplugin' ),
            [ $this, 'render_homepage_title_field' ],
            'seoplugin-settings',
            'seoplugin_general_section'
        );

        add_settings_field(
            'seoplugin_homepage_description',
            __( 'Homepage Meta Description', 'seoplugin' ),
            [ $this, 'render_homepage_description_field' ],
            'seoplugin-settings',
            'seoplugin_general_section'
        );

        add_settings_field(
            'seoplugin_default_meta_description',
            __( 'Default Meta Description', 'seoplugin' ),
            [ $this, 'render_default_meta_field' ],
            'seoplugin-settings',
            'seoplugin_general_section'
        );

        add_settings_field(
            'seoplugin_separator',
            __( 'Title Separator', 'seoplugin' ),
            [ $this, 'render_separator_field' ],
            'seoplugin-settings',
            'seoplugin_general_section'
        );

        // Social Media Section
        add_settings_section(
            'seoplugin_social_section',
            __( 'Social Media Settings', 'seoplugin' ),
            [ $this, 'render_social_section_description' ],
            'seoplugin-social'
        );

        add_settings_field(
            'seoplugin_facebook_app_id',
            __( 'Facebook App ID', 'seoplugin' ),
            [ $this, 'render_facebook_app_id_field' ],
            'seoplugin-social',
            'seoplugin_social_section'
        );

        add_settings_field(
            'seoplugin_twitter_username',
            __( 'Twitter Username', 'seoplugin' ),
            [ $this, 'render_twitter_username_field' ],
            'seoplugin-social',
            'seoplugin_social_section'
        );

        add_settings_field(
            'seoplugin_default_og_image',
            __( 'Default OG Image', 'seoplugin' ),
            [ $this, 'render_default_og_image_field' ],
            'seoplugin-social',
            'seoplugin_social_section'
        );

        // AI Settings Section
        add_settings_section(
            'seoplugin_ai_section',
            __( 'AI Assistant Settings', 'seoplugin' ),
            [ $this, 'render_ai_section_description' ],
            'seoplugin-ai'
        );

        add_settings_field(
            'seoplugin_ai_enabled',
            __( 'Enable AI Assistant', 'seoplugin' ),
            [ $this, 'render_ai_enabled_field' ],
            'seoplugin-ai',
            'seoplugin_ai_section'
        );

        add_settings_field(
            'seoplugin_ai_api_key',
            __( 'Google Gemini API Key', 'seoplugin' ),
            [ $this, 'render_ai_api_key_field' ],
            'seoplugin-ai',
            'seoplugin_ai_section'
        );

        // Advanced Settings Section
        add_settings_section(
            'seoplugin_advanced_section',
            __( 'Advanced SEO Settings', 'seoplugin' ),
            [ $this, 'render_advanced_section_description' ],
            'seoplugin-advanced'
        );

        add_settings_field(
            'seoplugin_xml_sitemap_enabled',
            __( 'Enable XML Sitemap', 'seoplugin' ),
            [ $this, 'render_xml_sitemap_field' ],
            'seoplugin-advanced',
            'seoplugin_advanced_section'
        );

        add_settings_field(
            'seoplugin_breadcrumbs_enabled',
            __( 'Enable Breadcrumbs', 'seoplugin' ),
            [ $this, 'render_breadcrumbs_field' ],
            'seoplugin-advanced',
            'seoplugin_advanced_section'
        );

        add_settings_field(
            'seoplugin_noindex_categories',
            __( 'Noindex Category Pages', 'seoplugin' ),
            [ $this, 'render_noindex_categories_field' ],
            'seoplugin-advanced',
            'seoplugin_advanced_section'
        );

        add_settings_field(
            'seoplugin_noindex_tags',
            __( 'Noindex Tag Pages', 'seoplugin' ),
            [ $this, 'render_noindex_tags_field' ],
            'seoplugin-advanced',
            'seoplugin_advanced_section'
        );

        add_settings_field(
            'seoplugin_noindex_archives',
            __( 'Noindex Archive Pages', 'seoplugin' ),
            [ $this, 'render_noindex_archives_field' ],
            'seoplugin-advanced',
            'seoplugin_advanced_section'
        );

        add_settings_field(
            'seoplugin_remove_category_base',
            __( 'Remove Category Base', 'seoplugin' ),
            [ $this, 'render_remove_category_base_field' ],
            'seoplugin-advanced',
            'seoplugin_advanced_section'
        );

        // Webmaster Tools Section
        add_settings_section(
            'seoplugin_webmaster_section',
            __( 'Webmaster Tools & Analytics', 'seoplugin' ),
            [ $this, 'render_webmaster_section_description' ],
            'seoplugin-webmaster'
        );

        add_settings_field(
            'seoplugin_google_verification',
            __( 'Google Search Console Verification', 'seoplugin' ),
            [ $this, 'render_google_verification_field' ],
            'seoplugin-webmaster',
            'seoplugin_webmaster_section'
        );

        add_settings_field(
            'seoplugin_bing_verification',
            __( 'Bing Webmaster Verification', 'seoplugin' ),
            [ $this, 'render_bing_verification_field' ],
            'seoplugin-webmaster',
            'seoplugin_webmaster_section'
        );

        add_settings_field(
            'seoplugin_yandex_verification',
            __( 'Yandex Webmaster Verification', 'seoplugin' ),
            [ $this, 'render_yandex_verification_field' ],
            'seoplugin-webmaster',
            'seoplugin_webmaster_section'
        );

        add_settings_field(
            'seoplugin_pinterest_verification',
            __( 'Pinterest Verification', 'seoplugin' ),
            [ $this, 'render_pinterest_verification_field' ],
            'seoplugin-webmaster',
            'seoplugin_webmaster_section'
        );

        add_settings_field(
            'seoplugin_google_analytics',
            __( 'Google Analytics Tracking ID', 'seoplugin' ),
            [ $this, 'render_google_analytics_field' ],
            'seoplugin-webmaster',
            'seoplugin_webmaster_section'
        );

        add_settings_field(
            'seoplugin_google_tag_manager',
            __( 'Google Tag Manager ID', 'seoplugin' ),
            [ $this, 'render_google_tag_manager_field' ],
            'seoplugin-webmaster',
            'seoplugin_webmaster_section'
        );
    }

    // Render settings page
    public function render_settings_page() {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <nav class="nav-tab-wrapper">
                <a href="?page=seoplugin-settings&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('General', 'seoplugin'); ?>
                </a>
                <a href="?page=seoplugin-settings&tab=social" class="nav-tab <?php echo $active_tab == 'social' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Social Media', 'seoplugin'); ?>
                </a>
                <a href="?page=seoplugin-settings&tab=ai" class="nav-tab <?php echo $active_tab == 'ai' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('AI Assistant', 'seoplugin'); ?>
                </a>
                <a href="?page=seoplugin-settings&tab=advanced" class="nav-tab <?php echo $active_tab == 'advanced' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Advanced', 'seoplugin'); ?>
                </a>
                <a href="?page=seoplugin-settings&tab=webmaster" class="nav-tab <?php echo $active_tab == 'webmaster' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Webmaster Tools', 'seoplugin'); ?>
                </a>
            </nav>

            <div class="tab-content">
                <?php if ($active_tab == 'general'): ?>
                    <form action="options.php" method="post">
                        <?php
                        settings_fields( 'seoplugin_general_group' );
                        do_settings_sections( 'seoplugin-settings' );
                        submit_button();
                        ?>
                    </form>
                <?php elseif ($active_tab == 'social'): ?>
                    <form action="options.php" method="post">
                        <?php
                        settings_fields( 'seoplugin_social_group' );
                        do_settings_sections( 'seoplugin-social' );
                        submit_button();
                        ?>
                    </form>
                <?php elseif ($active_tab == 'ai'): ?>
                    <form action="options.php" method="post">
                        <?php
                        settings_fields( 'seoplugin_ai_group' );
                        do_settings_sections( 'seoplugin-ai' );
                        submit_button();
                        ?>
                    </form>
                <?php elseif ($active_tab == 'advanced'): ?>
                    <form action="options.php" method="post">
                        <?php
                        settings_fields( 'seoplugin_advanced_group' );
                        do_settings_sections( 'seoplugin-advanced' );
                        submit_button();
                        ?>
                    </form>
                <?php elseif ($active_tab == 'webmaster'): ?>
                    <form action="options.php" method="post">
                        <?php
                        settings_fields( 'seoplugin_webmaster_group' );
                        do_settings_sections( 'seoplugin-webmaster' );
                        submit_button();
                        ?>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <style>
        .tab-content {
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            border-top: none;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .form-table th {
            width: 200px;
        }
        .seo-preview {
            background: #f8f9fa;
            border: 1px solid #e1e5e9;
            border-radius: 4px;
            padding: 15px;
            margin-top: 10px;
        }
        .seo-preview-title {
            color: #1a0dab;
            font-size: 18px;
            font-weight: 400;
            text-decoration: none;
            margin-bottom: 5px;
        }
        .seo-preview-url {
            color: #006621;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .seo-preview-description {
            color: #545454;
            font-size: 13px;
            line-height: 1.4;
        }
        </style>
        <?php
    }

    // Homepage title field
    public function render_homepage_title_field() {
        $setting = get_option( 'seoplugin_homepage_title', get_bloginfo('name') );
        ?>
        <input type="text" name="seoplugin_homepage_title" value="<?php echo esc_attr( $setting ); ?>" class="regular-text" />
        <p class="description"><?php esc_html_e( 'Custom title for your homepage. Leave empty to use site title.', 'seoplugin' ); ?></p>
        <?php
    }

    // Homepage description field
    public function render_homepage_description_field() {
        $setting = get_option( 'seoplugin_homepage_description', get_bloginfo('description') );
        ?>
        <textarea name="seoplugin_homepage_description" rows="3" class="large-text"><?php echo esc_textarea( $setting ); ?></textarea>
        <p class="description"><?php esc_html_e( 'Meta description for your homepage.', 'seoplugin' ); ?></p>
        <div class="seo-preview">
            <div class="seo-preview-title"><?php echo esc_html($setting ?: get_bloginfo('name')); ?></div>
            <div class="seo-preview-url"><?php echo esc_url(home_url()); ?></div>
            <div class="seo-preview-description"><?php echo esc_html($setting ?: get_bloginfo('description')); ?></div>
        </div>
        <?php
    }

    // Render default meta description field
    public function render_default_meta_field() {
        $setting = get_option( 'seoplugin_default_meta_description', '' );
        ?>
        <textarea name="seoplugin_default_meta_description" rows="3" class="large-text"><?php echo esc_textarea( $setting ); ?></textarea>
        <p class="description"><?php esc_html_e( 'Default meta description for pages without custom SEO settings.', 'seoplugin' ); ?></p>
        <?php
    }

    // Title separator field
    public function render_separator_field() {
        $setting = get_option( 'seoplugin_separator', '|' );
        $separators = [
            '|' => '|',
            '-' => '-',
            '–' => '–',
            '—' => '—',
            '·' => '·',
            '»' => '»',
            '/' => '/',
        ];
        ?>
        <select name="seoplugin_separator">
            <?php foreach ($separators as $value => $label): ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($setting, $value); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php esc_html_e( 'Character used to separate page title from site name.', 'seoplugin' ); ?></p>
        <?php
    }

    // Social section description
    public function render_social_section_description() {
        echo '<p>' . __('Configure social media settings for better sharing and engagement.', 'seoplugin') . '</p>';
    }

    // Facebook App ID field
    public function render_facebook_app_id_field() {
        $setting = get_option( 'seoplugin_facebook_app_id', '' );
        ?>
        <input type="text" name="seoplugin_facebook_app_id" value="<?php echo esc_attr( $setting ); ?>" class="regular-text" />
        <p class="description"><?php esc_html_e( 'Facebook App ID for better social sharing analytics.', 'seoplugin' ); ?></p>
        <?php
    }

    // Twitter username field
    public function render_twitter_username_field() {
        $setting = get_option( 'seoplugin_twitter_username', '' );
        ?>
        <input type="text" name="seoplugin_twitter_username" value="<?php echo esc_attr( $setting ); ?>" class="regular-text" placeholder="@username" />
        <p class="description"><?php esc_html_e( 'Your Twitter username (including @) for Twitter Card attribution.', 'seoplugin' ); ?></p>
        <?php
    }

    // Default OG Image field
    public function render_default_og_image_field() {
        $setting = get_option( 'seoplugin_default_og_image', '' );
        $image_url = '';
        if ($setting) {
            $image_url = wp_get_attachment_image_url($setting, 'medium');
        }
        ?>
        <div class="default-og-image-container">
            <input type="hidden" name="seoplugin_default_og_image" id="seoplugin_default_og_image" value="<?php echo esc_attr($setting); ?>" />
            <div class="image-preview" id="default-og-preview">
                <?php if ($image_url): ?>
                    <img src="<?php echo esc_url($image_url); ?>" style="max-width: 300px; height: auto;" />
                <?php endif; ?>
            </div>
            <button type="button" class="button" id="select-default-og-image">
                <?php _e('Select Default OG Image', 'seoplugin'); ?>
            </button>
            <?php if ($setting): ?>
                <button type="button" class="button" id="remove-default-og-image">
                    <?php _e('Remove Image', 'seoplugin'); ?>
                </button>
            <?php endif; ?>
        </div>
        <p class="description"><?php esc_html_e( 'Default Open Graph image used when no specific image is set for a page.', 'seoplugin' ); ?></p>
        
        <script>
        jQuery(document).ready(function($) {
            $('#select-default-og-image').click(function(e) {
                e.preventDefault();
                var frame = wp.media({
                    title: 'Select Default OG Image',
                    button: { text: 'Use This Image' },
                    multiple: false
                });
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#seoplugin_default_og_image').val(attachment.id);
                    $('#default-og-preview').html('<img src="' + attachment.url + '" style="max-width: 300px; height: auto;" />');
                });
                frame.open();
            });
            
            $('#remove-default-og-image').click(function(e) {
                e.preventDefault();
                $('#seoplugin_default_og_image').val('');
                $('#default-og-preview').empty();
                $(this).hide();
            });
        });
        </script>
        <?php
    }

    // AI section description
    public function render_ai_section_description() {
        echo '<p>' . __('Configure AI-powered SEO assistance using Google Gemini API.', 'seoplugin') . '</p>';
    }

    // AI enabled field
    public function render_ai_enabled_field() {
        $setting = get_option( 'seoplugin_ai_enabled', true );
        ?>
        <label>
            <input type="checkbox" name="seoplugin_ai_enabled" value="1" <?php checked($setting, 1); ?> />
            <?php _e('Enable AI-powered SEO suggestions', 'seoplugin'); ?>
        </label>
        <p class="description"><?php esc_html_e( 'Enable or disable AI assistant features in the post editor.', 'seoplugin' ); ?></p>
        <?php
    }

    // Render AI API key field
    public function render_ai_api_key_field() {
        $setting = get_option( 'seoplugin_ai_api_key', '' );
        ?>
        <input type="password" name="seoplugin_ai_api_key" value="<?php echo esc_attr( $setting ); ?>" class="regular-text" />
        <p class="description">
            <?php printf(
                __('Enter your Google Gemini API key for AI-powered SEO suggestions. Get your key from <a href="%s" target="_blank">Google AI Studio</a>.', 'seoplugin'),
                'https://makersuite.google.com/app/apikey'
            ); ?>
        </p>
        <?php
    }

    // Advanced section description
    public function render_advanced_section_description() {
        echo '<p>' . __('Advanced SEO settings for fine-tuning your site optimization.', 'seoplugin') . '</p>';
    }

    // XML Sitemap field
    public function render_xml_sitemap_field() {
        $setting = get_option( 'seoplugin_xml_sitemap_enabled', true );
        ?>
        <label>
            <input type="checkbox" name="seoplugin_xml_sitemap_enabled" value="1" <?php checked($setting, 1); ?> />
            <?php _e('Generate XML sitemap', 'seoplugin'); ?>
        </label>
        <p class="description">
            <?php printf(
                __('Automatically generate XML sitemap at <a href="%s" target="_blank">%s</a>', 'seoplugin'),
                home_url('/sitemap.xml'),
                home_url('/sitemap.xml')
            ); ?>
        </p>
        <?php
    }

    // Breadcrumbs field
    public function render_breadcrumbs_field() {
        $setting = get_option( 'seoplugin_breadcrumbs_enabled', true );
        ?>
        <label>
            <input type="checkbox" name="seoplugin_breadcrumbs_enabled" value="1" <?php checked($setting, 1); ?> />
            <?php _e('Enable breadcrumbs functionality', 'seoplugin'); ?>
        </label>
        <p class="description"><?php esc_html_e( 'Use [seoplugin_breadcrumbs] shortcode to display breadcrumbs.', 'seoplugin' ); ?></p>
        <?php
    }

    // Noindex categories field
    public function render_noindex_categories_field() {
        $setting = get_option( 'seoplugin_noindex_categories', false );
        ?>
        <label>
            <input type="checkbox" name="seoplugin_noindex_categories" value="1" <?php checked($setting, 1); ?> />
            <?php _e('Add noindex to category pages', 'seoplugin'); ?>
        </label>
        <p class="description"><?php esc_html_e( 'Prevent search engines from indexing category archive pages.', 'seoplugin' ); ?></p>
        <?php
    }

    // Noindex tags field
    public function render_noindex_tags_field() {
        $setting = get_option( 'seoplugin_noindex_tags', false );
        ?>
        <label>
            <input type="checkbox" name="seoplugin_noindex_tags" value="1" <?php checked($setting, 1); ?> />
            <?php _e('Add noindex to tag pages', 'seoplugin'); ?>
        </label>
        <p class="description"><?php esc_html_e( 'Prevent search engines from indexing tag archive pages.', 'seoplugin' ); ?></p>
        <?php
    }

    // Noindex archives field
    public function render_noindex_archives_field() {
        $setting = get_option( 'seoplugin_noindex_archives', false );
        ?>
        <label>
            <input type="checkbox" name="seoplugin_noindex_archives" value="1" <?php checked($setting, 1); ?> />
            <?php _e('Add noindex to date/author archives', 'seoplugin'); ?>
        </label>
        <p class="description"><?php esc_html_e( 'Prevent search engines from indexing date and author archive pages.', 'seoplugin' ); ?></p>
        <?php
    }

    // Remove category base field
    public function render_remove_category_base_field() {
        $setting = get_option( 'seoplugin_remove_category_base', false );
        ?>
        <label>
            <input type="checkbox" name="seoplugin_remove_category_base" value="1" <?php checked($setting, 1); ?> />
            <?php _e('Remove /category/ from URLs', 'seoplugin'); ?>
        </label>
        <p class="description"><?php esc_html_e( 'Remove the category base from category URLs for cleaner permalinks.', 'seoplugin' ); ?></p>
        <?php
    }

    // Webmaster section description
    public function render_webmaster_section_description() {
        echo '<p>' . __('Connect your site with webmaster tools and analytics services.', 'seoplugin') . '</p>';
    }

    // Google verification field
    public function render_google_verification_field() {
        $setting = get_option( 'seoplugin_google_verification', '' );
        ?>
        <input type="text" name="seoplugin_google_verification" value="<?php echo esc_attr( $setting ); ?>" class="regular-text" placeholder="google1234567890abcdef.html" />
        <p class="description"><?php esc_html_e( 'Google Search Console verification meta tag content.', 'seoplugin' ); ?></p>
        <?php
    }

    // Bing verification field
    public function render_bing_verification_field() {
        $setting = get_option( 'seoplugin_bing_verification', '' );
        ?>
        <input type="text" name="seoplugin_bing_verification" value="<?php echo esc_attr( $setting ); ?>" class="regular-text" />
        <p class="description"><?php esc_html_e( 'Bing Webmaster Tools verification meta tag content.', 'seoplugin' ); ?></p>
        <?php
    }

    // Yandex verification field
    public function render_yandex_verification_field() {
        $setting = get_option( 'seoplugin_yandex_verification', '' );
        ?>
        <input type="text" name="seoplugin_yandex_verification" value="<?php echo esc_attr( $setting ); ?>" class="regular-text" />
        <p class="description"><?php esc_html_e( 'Yandex Webmaster verification meta tag content.', 'seoplugin' ); ?></p>
        <?php
    }

    // Pinterest verification field
    public function render_pinterest_verification_field() {
        $setting = get_option( 'seoplugin_pinterest_verification', '' );
        ?>
        <input type="text" name="seoplugin_pinterest_verification" value="<?php echo esc_attr( $setting ); ?>" class="regular-text" />
        <p class="description"><?php esc_html_e( 'Pinterest site verification meta tag content.', 'seoplugin' ); ?></p>
        <?php
    }

    // Google Analytics field
    public function render_google_analytics_field() {
        $setting = get_option( 'seoplugin_google_analytics', '' );
        ?>
        <input type="text" name="seoplugin_google_analytics" value="<?php echo esc_attr( $setting ); ?>" class="regular-text" placeholder="G-XXXXXXXXXX or UA-XXXXXXXX-X" />
        <p class="description"><?php esc_html_e( 'Google Analytics tracking ID (GA4 or Universal Analytics).', 'seoplugin' ); ?></p>
        <?php
    }

    // Google Tag Manager field
    public function render_google_tag_manager_field() {
        $setting = get_option( 'seoplugin_google_tag_manager', '' );
        ?>
        <input type="text" name="seoplugin_google_tag_manager" value="<?php echo esc_attr( $setting ); ?>" class="regular-text" placeholder="GTM-XXXXXXX" />
        <p class="description"><?php esc_html_e( 'Google Tag Manager container ID.', 'seoplugin' ); ?></p>
        <?php
    }

    // Add meta box for posts/pages
    public function add_meta_box() {
        $post_types = get_post_types(['public' => true], 'names');
        foreach ($post_types as $post_type) {
        add_meta_box(
            'seoplugin_meta_box',
            __( 'SEO Settings', 'seoplugin' ),
            [ $this, 'render_meta_box' ],
                $post_type,
            'normal',
            'high'
        );
        }
    }

    // Render meta box
    public function render_meta_box( $post ) {
        $title = get_post_meta($post->ID, '_seoplugin_meta_title', true) ?: get_the_title($post->ID);
        $description = get_post_meta($post->ID, '_seoplugin_meta_description', true) ?: get_the_title($post->ID);
        $og_image_id = get_post_meta($post->ID, '_seoplugin_og_image_id', true);
        wp_nonce_field('seoplugin_meta_box', 'seoplugin_meta_box_nonce');
        
        // Load custom image size og_custom
        if (is_numeric($og_image_id)) {
            $og_img = wp_get_attachment_image_src($og_image_id, 'og_custom');
            $og_image_url = !empty($og_img) ? $og_img[0] : '';
        } else {
            $og_image_url = esc_url($og_image_id);
        }
?>
<div class="seoplugin_eseo">
    <div style="margin-top: 12px;"><strong class="control-label">Search Appearance</strong></div>
    <div class="google-view">
        <div class="google-wrap-content">
            <div class="header-logo">
                <div class="divddercolunm">
                    <div class="google-logo"><?php if (!empty(get_site_icon_url())) : ?><img class="logo" src="<?=get_site_icon_url()?>"/><?php endif; ?></div>
                    <div class="google-site">
                        <span class="google-site-domain"><?=get_bloginfo('name')?></span><br>
                        <span class="site-down-color"><?=do_shortcode('[site_domain_url]');?></span>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#70757a" style="width: 18px;margin-top: -30px;"><path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"></path></svg>
                </div>
            </div>
            <div class="wrp-google-title"><div class="google-title"><span class="google-title3"><?=esc_attr($title)?></span></div></div>
            <div class="wrp-google-decription"><span class="site-down-color"><?=date('M d, Y')?> - </span><?=esc_textarea($description)?></div>
        </div>
    </div>
    <p>
        <label for="seoplugin_meta_title"><strong class="control-label">SEO Page Title <span id="seoTitleCharCount">0</span>/65</strong></label><br>
        <input class="frmtxt" type="text" name="seoplugin_meta_title" id="seoplugin_meta_title" value="<?php echo esc_attr($title); ?>" maxlength="65" style="width:100%;" />
    </p>
    <p>
        <label for="seoplugin_meta_description"><strong class="control-label">Meta Description <span id="seoDescriptionCharCount">0</span>/160</strong></label><br>
        <textarea class="frmtxt" name="seoplugin_meta_description" id="seoplugin_meta_description" rows="4" maxlength="160" style="width:100%;"><?php echo esc_textarea($description); ?></textarea>
    </p>
    <p>
        <label for="seoplugin_og_image_button"><strong class="control-label">OG Image</strong></label><br>
        <input type="hidden" name="seoplugin_og_image_id" id="seoplugin_og_image_id" value="<?php echo esc_attr($og_image_id); ?>" />
        <span class="seoplugin_og_image_preview" id="seoplugin_og_image_preview">
            <?php if (!empty($og_image_url)) : ?>
                <img src="<?php echo esc_url($og_image_url); ?>" alt="OG images" style="width:527px; height:352px; object-fit:cover;" />
            <?php endif; ?>
        </span>
        <span class="wrp-contact-social">
            <span class="site-domain"><?=do_shortcode('[site_domain_url]');?></span>
            <span class="socail-title"><?=esc_attr($title);?></span>
            <span class="socail-description"><?=esc_textarea($description);?></span>
        </span>
        <button type="button" class="button" id="seoplugin_og_image_button">Select OG Image</button>
    </p>
    
    <!-- SEO Analysis Section -->
    <div class="seo-analysis-section">
        <h3><strong class="control-label">SEO Analysis</strong></h3>
        <div class="seo-score-container">
            <div class="seo-score-circle">
                <span id="seo-score">0</span>
                <small>Score</small>
            </div>
            <div class="seo-analysis-details">
                <div class="seo-item" id="title-analysis">
                    <span class="seo-icon">📝</span>
                    <span class="seo-text">Title Length</span>
                    <span class="seo-status" id="title-status">-</span>
                </div>
                <div class="seo-item" id="desc-analysis">
                    <span class="seo-icon">📄</span>
                    <span class="seo-text">Description Length</span>
                    <span class="seo-status" id="desc-status">-</span>
                </div>
                <div class="seo-item" id="image-analysis">
                    <span class="seo-icon">🖼️</span>
                    <span class="seo-text">OG Image</span>
                    <span class="seo-status" id="image-status">-</span>
                </div>
                <div class="seo-item" id="keyword-analysis">
                    <span class="seo-icon">🔍</span>
                    <span class="seo-text">Keyword Density</span>
                    <span class="seo-status" id="keyword-status">-</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Social Media Previews -->
    <div class="social-previews">
        <h3><strong class="control-label">Social Media Previews</strong></h3>
        <div class="social-tabs">
            <button class="social-tab active" data-tab="facebook">Facebook</button>
            <button class="social-tab" data-tab="x">X (Twitter)</button>
        </div>
        
        <div class="social-preview-content">
            <div class="social-preview facebook-preview active" id="facebook-preview">
                <div class="social-card">
                    <div class="social-image">
                        <?php if (!empty($og_image_url)) : ?>
                            <img src="<?php echo esc_url($og_image_url); ?>" alt="Social preview" />
                        <?php else : ?>
                            <div class="no-image">No image selected</div>
                        <?php endif; ?>
                    </div>
                    <div class="social-content">
                        <div class="social-url"><?=do_shortcode('[site_domain_url]');?></div>
                        <div class="social-title"><?=esc_attr($title);?></div>
                        <div class="social-description"><?=esc_textarea($description);?></div>
                    </div>
                </div>
            </div>
            
            <div class="social-preview twitter-preview" id="x-preview">
                <div class="x-card">
                    <div class="x-image">
                        <?php if (!empty($og_image_url)) : ?>
                            <img src="<?php echo esc_url($og_image_url); ?>" alt="Twitter preview" />
                        <?php else : ?>
                            <div class="no-image">No image selected</div>
                        <?php endif; ?>
                    </div>
                    <div class="x-content">
                        <div class="x-title"><?=esc_attr($title);?></div>
                        <div class="x-description"><?=esc_textarea($description);?></div>
                        <div class="x-url"><?=do_shortcode('[site_domain_url]');?></div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>

    <!-- AI-Powered SEO Assistant -->
    <div class="ai-seo-assistant">
        <h3><strong class="control-label">🤖 AI SEO Assistant</strong></h3>
        <div class="ai-controls">
            <button type="button" class="button button-primary" id="ai-generate-title">Generate Title</button>
            <button type="button" class="button button-primary" id="ai-generate-description">Generate Description</button>
            <button type="button" class="button button-secondary" id="ai-suggest-keywords">Suggest Keywords</button>
            <button type="button" class="button button-secondary" id="ai-analyze-content">Analyze Content</button>
        </div>
        <div class="ai-suggestions" id="ai-suggestions" style="display: none;">
            <h4>AI Suggestions:</h4>
            <div class="ai-suggestion-content" id="ai-suggestion-content"></div>
        </div>
        <div class="ai-loading" id="ai-loading" style="display: none;">
            <div class="spinner"></div>
            <span>AI is working...</span>
        </div>
    </div>

    <!-- Advanced SEO Options -->
    <div class="advanced-seo-options">
        <h3><strong class="control-label">Advanced SEO Options</strong></h3>
        
        <p>
            <label for="seoplugin_focus_keyword"><strong class="control-label">Focus Keyword</strong></label><br>
            <input class="frmtxt" type="text" name="seoplugin_focus_keyword" id="seoplugin_focus_keyword" value="<?php echo esc_attr(get_post_meta($post->ID, '_seoplugin_focus_keyword', true)); ?>" placeholder="Enter your main keyword" style="width:100%;" />
            <small class="description">The main keyword you want to rank for</small>
        </p>

        <p>
            <label for="seoplugin_robots_meta"><strong class="control-label">Robots Meta</strong></label><br>
            <select class="frmtxt" name="seoplugin_robots_meta" id="seoplugin_robots_meta" style="width:100%;">
                <option value="">Default</option>
                <option value="index,follow" <?php selected(get_post_meta($post->ID, '_seoplugin_robots_meta', true), 'index,follow'); ?>>Index, Follow</option>
                <option value="noindex,follow" <?php selected(get_post_meta($post->ID, '_seoplugin_robots_meta', true), 'noindex,follow'); ?>>No Index, Follow</option>
                <option value="index,nofollow" <?php selected(get_post_meta($post->ID, '_seoplugin_robots_meta', true), 'index,nofollow'); ?>>Index, No Follow</option>
                <option value="noindex,nofollow" <?php selected(get_post_meta($post->ID, '_seoplugin_robots_meta', true), 'noindex,nofollow'); ?>>No Index, No Follow</option>
            </select>
        </p>

        <p>
            <label for="seoplugin_canonical_url"><strong class="control-label">Canonical URL</strong></label><br>
            <input class="frmtxt" type="url" name="seoplugin_canonical_url" id="seoplugin_canonical_url" value="<?php echo esc_attr(get_post_meta($post->ID, '_seoplugin_canonical_url', true)); ?>" placeholder="https://example.com/canonical-page" style="width:100%;" />
            <small class="description">Override the canonical URL for this page</small>
        </p>

        <p>
            <label for="seoplugin_schema_type"><strong class="control-label">Schema Type</strong></label><br>
            <select class="frmtxt" name="seoplugin_schema_type" id="seoplugin_schema_type" style="width:100%;">
                <option value="Article" <?php selected(get_post_meta($post->ID, '_seoplugin_schema_type', true), 'Article'); ?>>Article</option>
                <option value="BlogPosting" <?php selected(get_post_meta($post->ID, '_seoplugin_schema_type', true), 'BlogPosting'); ?>>Blog Post</option>
                <option value="WebPage" <?php selected(get_post_meta($post->ID, '_seoplugin_schema_type', true), 'WebPage'); ?>>Web Page</option>
                <option value="Product" <?php selected(get_post_meta($post->ID, '_seoplugin_schema_type', true), 'Product'); ?>>Product</option>
                <option value="Event" <?php selected(get_post_meta($post->ID, '_seoplugin_schema_type', true), 'Event'); ?>>Event</option>
                <option value="Recipe" <?php selected(get_post_meta($post->ID, '_seoplugin_schema_type', true), 'Recipe'); ?>>Recipe</option>
                <option value="Review" <?php selected(get_post_meta($post->ID, '_seoplugin_schema_type', true), 'Review'); ?>>Review</option>
            </select>
        </p>

        <p>
            <label for="seoplugin_meta_keywords"><strong class="control-label">Meta Keywords (comma separated)</strong></label><br>
            <input class="frmtxt" type="text" name="seoplugin_meta_keywords" id="seoplugin_meta_keywords" value="<?php echo esc_attr(get_post_meta($post->ID, '_seoplugin_meta_keywords', true)); ?>" placeholder="keyword1, keyword2, keyword3" style="width:100%;" />
            <small class="description">Separate keywords with commas</small>
        </p>
    </div>
</div>
        <?php
    }

    // Save meta box data
    public function save_meta_box( $post_id ) {
        if ( ! isset( $_POST['seoplugin_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['seoplugin_meta_box_nonce'], 'seoplugin_meta_box' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if (isset($_POST['seoplugin_og_image_id'])) {
            update_post_meta($post_id, '_seoplugin_og_image_id', absint($_POST['seoplugin_og_image_id']));
        }

        if (isset($_POST['seoplugin_meta_title'])) {
            update_post_meta($post_id, '_seoplugin_meta_title', sanitize_text_field($_POST['seoplugin_meta_title']));
        }

        if (isset($_POST['seoplugin_meta_description'])) {
            update_post_meta($post_id, '_seoplugin_meta_description', sanitize_textarea_field($_POST['seoplugin_meta_description']));
        }

        if (isset($_POST['seoplugin_focus_keyword'])) {
            update_post_meta($post_id, '_seoplugin_focus_keyword', sanitize_text_field($_POST['seoplugin_focus_keyword']));
        }

        if (isset($_POST['seoplugin_robots_meta'])) {
            update_post_meta($post_id, '_seoplugin_robots_meta', sanitize_text_field($_POST['seoplugin_robots_meta']));
        }

        if (isset($_POST['seoplugin_canonical_url'])) {
            update_post_meta($post_id, '_seoplugin_canonical_url', esc_url_raw($_POST['seoplugin_canonical_url']));
        }

        if (isset($_POST['seoplugin_schema_type'])) {
            update_post_meta($post_id, '_seoplugin_schema_type', sanitize_text_field($_POST['seoplugin_schema_type']));
        }

        if (isset($_POST['seoplugin_meta_keywords'])) {
            update_post_meta($post_id, '_seoplugin_meta_keywords', sanitize_text_field($_POST['seoplugin_meta_keywords']));
        }
    }

    // Enqueue admin scripts and styles
    public function enqueue_admin_scripts($hook) {
        if (in_array($hook, ['post.php', 'post-new.php', 'settings_page_seoplugin-settings'])) {
            wp_enqueue_media();
            wp_enqueue_script('seoplugin-admin-meta', SEOPLUGIN_URL . 'assets/js/seoplugin.js', ['jquery'], SEOPLUGIN_VERSION, true);
            wp_enqueue_style('seoplugin-admin-meta-css', SEOPLUGIN_URL . 'assets/css/seoplugin.css', array(), SEOPLUGIN_VERSION);
            wp_localize_script('seoplugin-admin-meta', 'seoplugin_ajax', ['ajax_url' => admin_url('admin-ajax.php')]);
        }
    }

    // Register custom image sizes
    public function register_image_sizes() {
        add_image_size('og_custom', 1024, 683, true);
    }

    // AJAX handler to generate og_custom size on demand
    public function regen_og_custom_callback() {
        $attachment_id = absint($_POST['attachment_id'] ?? 0);
        if (!$attachment_id) {
            wp_send_json_error('Missing attachment ID');
        }

        $fullsizepath = get_attached_file($attachment_id);
        if (!$fullsizepath || !file_exists($fullsizepath)) {
            wp_send_json_error('File not found.');
        }

        $mime = mime_content_type($fullsizepath);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'])) {
            wp_send_json_error('Only JPEG, PNG, and WebP are supported.');
        }

        // Load image editor
        $editor = wp_get_image_editor($fullsizepath);
        if (is_wp_error($editor)) {
            wp_send_json_error('Image editor error: ' . $editor->get_error_message());
        }

        // Regenerate og_custom
        $editor->multi_resize([
            'og_custom' => [1024, 683, true]
        ]);

        // Update metadata
        $metadata = wp_generate_attachment_metadata($attachment_id, $fullsizepath);
        if (is_wp_error($metadata)) {
            wp_send_json_error('Metadata error: ' . $metadata->get_error_message());
        }

        wp_update_attachment_metadata($attachment_id, $metadata);

        // Try to retrieve the new image URL
        if (!empty($metadata['sizes']['og_custom']['file'])) {
            $upload_dir = wp_upload_dir();
            $baseurl = trailingslashit($upload_dir['baseurl']);
            $basedir = trailingslashit($upload_dir['basedir']);
            $subdir = dirname($metadata['file']);

            $og_file = $metadata['sizes']['og_custom']['file'];
            $og_path = $basedir . '/' . $subdir . '/' . $og_file;
            $og_url  = $baseurl . $subdir . '/' . $og_file;

            if (file_exists($og_path)) {
                wp_send_json_success(['url' => esc_url($og_url)]);
            }
        }

        wp_send_json_error('Image too smaller than 1024x863 PX.');
    }

    // AI-powered title generation
    public function ai_generate_title_callback() {
        $api_key = get_option('seoplugin_ai_api_key');
        if (!$api_key) {
            wp_send_json_error('API key not configured');
        }

        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);
        $content = wp_strip_all_tags($post->post_content);
        $focus_keyword = get_post_meta($post_id, '_seoplugin_focus_keyword', true);

        $prompt = "Generate 3 SEO-optimized titles for this content. Requirements: 30-65 characters, include focus keyword '{$focus_keyword}', engaging and click-worthy. Content: " . substr($content, 0, 1000);
        
        $result = $this->call_gemini_api($api_key, $prompt);
        if ($result) {
            wp_send_json_success(['titles' => $result]);
        } else {
            wp_send_json_error('Failed to generate titles');
        }
    }

    // AI-powered description generation
    public function ai_generate_description_callback() {
        $api_key = get_option('seoplugin_ai_api_key');
        if (!$api_key) {
            wp_send_json_error('API key not configured');
        }

        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);
        $content = wp_strip_all_tags($post->post_content);
        $focus_keyword = get_post_meta($post_id, '_seoplugin_focus_keyword', true);

        $prompt = "Generate 3 SEO-optimized meta descriptions for this content. Requirements: 120-160 characters, include focus keyword '{$focus_keyword}', compelling and descriptive. Content: " . substr($content, 0, 1000);
        
        $result = $this->call_gemini_api($api_key, $prompt);
        if ($result) {
            wp_send_json_success(['descriptions' => $result]);
        } else {
            wp_send_json_error('Failed to generate descriptions');
        }
    }

    // AI-powered keyword suggestions
    public function ai_suggest_keywords_callback() {
        $api_key = get_option('seoplugin_ai_api_key');
        if (!$api_key) {
            wp_send_json_error('API key not configured');
        }

        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);
        $content = wp_strip_all_tags($post->post_content);

        $prompt = "Suggest 10 relevant SEO keywords for this content. Include primary and long-tail keywords. Format as comma-separated list. Content: " . substr($content, 0, 1000);
        
        $result = $this->call_gemini_api($api_key, $prompt);
        if ($result) {
            wp_send_json_success(['keywords' => $result]);
        } else {
            wp_send_json_error('Failed to generate keywords');
        }
    }

    // AI-powered content analysis
    public function ai_analyze_content_callback() {
        $api_key = get_option('seoplugin_ai_api_key');
        if (!$api_key) {
            wp_send_json_error('API key not configured');
        }

        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);
        $content = wp_strip_all_tags($post->post_content);
        $title = get_post_meta($post_id, '_seoplugin_meta_title', true) ?: $post->post_title;
        $description = get_post_meta($post_id, '_seoplugin_meta_description', true);

        $prompt = "Analyze this content for SEO optimization. Provide specific recommendations for: 1) Title optimization, 2) Meta description improvement, 3) Content structure, 4) Keyword usage, 5) Readability. Be specific and actionable. Content: " . substr($content, 0, 1500) . " | Title: {$title} | Description: {$description}";
        
        $result = $this->call_gemini_api($api_key, $prompt);
        if ($result) {
            wp_send_json_success(['analysis' => $result]);
        } else {
            wp_send_json_error('Failed to analyze content');
        }
    }

    // Call Gemini API
    private function call_gemini_api($api_key, $prompt) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $api_key;
        
        $data = [
            "contents" => [[
                "parts" => [[ "text" => $prompt ]]
            ]]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        if ($response === false) {
            curl_close($ch);
            return false;
        }
        curl_close($ch);

        $result = json_decode($response, true);
        if (isset($result["candidates"][0]["content"]["parts"][0]["text"])) {
            return $result["candidates"][0]["content"]["parts"][0]["text"];
        }
        
        return false;
    }
}

new SEOPlugin_Admin();