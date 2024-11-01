<?php

/*
 * class ProfitsharePlugin
 *
 * Used to make all wordpress operations
 */

class PWA_Plugin
{
    // plugin main file
    private $file;

    // plugin version
    private $version;

    // full plugin path
    private $plugin_path;

    // full plugin url
    private $plugin_url;

    // path to library dir
    private $includes_path;

    // plugin errors for module settings
    private $errors;

    // Profitshare Class instance
    public $profitshare;

    // module settings
    private $settings;

    // key constants for holding module options in database
    const PLUGIN_OPTION_COUNTRY = "profitshare_woocommerce_country";
    const PLUGIN_OPTION_ADVERTISER_KEY = "profitshare_woocommerce_key";
    const PLUGIN_OPTION_ENCRYPTION_KEY = "profitshare_woocommerce_encryption_key";
    const PLUGIN_OPTION_CLICK_CODE = "profitshare_woocommerce_click_code";
    const PLUGIN_OPTION_TRACKING_URL = "profitshare_woocommerce_tracking_url";
    const PLUGIN_OPTION_VAT_KEY = "profitshare_woocommerce_vat";
    const PLUGIN_OPTION_FEED_FILE_NAME = "profitshare_feed_file_name";

    // exchange module options, by default currency is RON so the exchange value will be 1
    const PLUGIN_OPTION_EXCHANGE_VALUE = "profitshare_exchange_value";
    const PLUGIN_OPTION_DEFAULT_EXCHANGE_VALUE = 1.00;

    const ACTION_ACTIVATE = 'activate';
    const ACTION_DEACTIVATE = 'deactivate';

    const ASSETS_FILES_PATH = "assets";

    const PLUGIN_NAME = "wp-profitshare-advertisers";

    const DEFAULT_CURRENCY = "RON";

    public function __construct($file, $version)
    {
        $this->file = $file;
        $this->version = $version;

        $this->plugin_path = trailingslashit(plugin_dir_path($this->file));
        $this->plugin_url = trailingslashit(plugin_dir_url($this->file));
        $this->includes_path = $this->plugin_path . trailingslashit('includes');

        require_once($this->includes_path . 'controllers/class-PWA-core.php');

        $this->profitshare = new PWA_Core(get_option(self::PLUGIN_OPTION_COUNTRY, null));
        $this->require_classes();

        $this->settings = new PWA_Settings();
    }

    /**
     * Start profitshare plugin
     */
    public function start()
    {
        register_activation_hook($this->file, [$this, 'install']);
        register_deactivation_hook($this->file, [$this, 'uninstall']);

        add_filter('plugin_action_links_' . plugin_basename($this->file), [$this, 'plugin_action_links']);
        add_action('admin_menu', [$this, 'register_settings_page']);

        // register actions if plugin is active
        if ($this->is_plugin_active()) {
            $this->register_actions();
        }
    }

    /*
     * Install profitshare plugin
     */
    public function install()
    {
        if (!function_exists('WC')) {
            die('To use our module you have to install WooCommmerce plugin!');
        }

        // create tables
        //$this->create_tables();

        // update profitshare categories
        //$this->profitshare->update_categories();
    }

    /**
     * Uninstall profitshare plugin
     */
    public function uninstall()
    {
        // drop tables
        //$this->delete_tables();

        // remove options
        $this->delete_options();

        // deactivate crons
        $this->deactivate_crons();
    }

    /*
     * Create module tabels
     */
    private function create_tables()
    {
        global $wpdb;

        $wpdb->query('CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'profitshare_categories` (
             `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
             `id_category` INT( 11 ) UNSIGNED NOT NULL,
             `name` varchar(255) NOT NULL,
             `id_parent` INT( 11 ) UNSIGNED NOT NULL,
             `status` enum("active", "inactive") default "active",
             `updated_at` TIMESTAMP NOT NULL DEFAULT NOW() ON UPDATE NOW(),
             `created_at` TIMESTAMP NOT NULL DEFAULT NOW(),
             PRIMARY KEY (`id`),
             UNIQUE  `CATEGORY_UNIQUE` (  `id_category` )
         )
         ENGINE=InnoDB DEFAULT CHARSET=utf8;');

        $wpdb->query('CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'profitshare_wordpress_categories` (
             `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
             `profitshare_category_id` INT( 11 ) UNSIGNED NOT NULL,
             `wordpress_category_id` INT( 11 ) UNSIGNED NOT NULL,
             `category_commission` INT(2) ,
             `updated_at` TIMESTAMP NOT NULL DEFAULT NOW() ON UPDATE NOW(),
             `created_at` TIMESTAMP NOT NULL DEFAULT NOW(),
             PRIMARY KEY (`id`),
             UNIQUE  `wordpress_CATEGORY_UNIQUE` (`wordpress_category_id`)
         )
         ENGINE= InnoDB DEFAULT CHARSET=utf8;');
    }

    /*
     * Delete module tables
     */
    private function delete_tables()
    {
        global $wpdb;
        $wpdb->query('DROP TABLE IF EXISTS `' . $wpdb->prefix . 'profitshare_categories`;');
        $wpdb->query('DROP TABLE IF EXISTS `' . $wpdb->prefix . 'profitshare_wordpress_categories`;');
    }

    /**
     * Delete module options
     */
    private function delete_options()
    {
        delete_option(self::PLUGIN_OPTION_COUNTRY);
        delete_option(self::PLUGIN_OPTION_VAT_KEY);
        delete_option(self::PLUGIN_OPTION_CLICK_CODE);
        delete_option(self::PLUGIN_OPTION_TRACKING_URL);
        delete_option(self::PLUGIN_OPTION_ADVERTISER_KEY);
        delete_option(self::PLUGIN_OPTION_ENCRYPTION_KEY);
        delete_option(self::PLUGIN_OPTION_FEED_FILE_NAME);
    }

    /**
     * Deactive module crons
     */
    private function deactivate_crons()
    {
        wp_clear_scheduled_hook('profitshare_feed_event');
        wp_clear_scheduled_hook('profitshare_exchange_event');
    }


    /**
     * Link to settings screen.
     */
    public function get_admin_setting_link()
    {
        return admin_url('admin.php?page=ps_woocommerce_account_settings');
    }

    /**
     * Add relevant links to plugins page.
     *
     * @param array $links Plugin action links
     * @return array Plugin action links
     */
    public function plugin_action_links($links)
    {
        $plugin_links = [];

        $setting_url = $this->get_admin_setting_link();
        $plugin_links[] = '<a href="' . esc_url($setting_url) . '">' . esc_html__('Settings',
                'wp-profitshare-advertisers') . '</a>';

        return array_merge($plugin_links, $links);
    }

    /*
     * Get assets path url to $file
     * @param string $file
     * @return string
     */
    public function getAssetsUrl($file)
    {
        return plugins_url(self::PLUGIN_NAME . '/assets/' . $file);
    }

    /**
     * @param $file
     * @return string
     */
    public function getAssetsPath($file)
    {
        $module_path = plugin_dir_path(dirname(__DIR__));

        return $module_path . $file;
    }

    /**
     * Verify if the plugin is active.
     *
     * @return boolean
     */
    public function is_plugin_active()
    {
        return (!empty(get_option(self::PLUGIN_OPTION_COUNTRY, null))
            && !empty(get_option(self::PLUGIN_OPTION_CLICK_CODE, null))
            && !empty(get_option(self::PLUGIN_OPTION_ENCRYPTION_KEY, null))
            && !empty(get_option(self::PLUGIN_OPTION_ADVERTISER_KEY, null))
            && !empty(get_option(self::PLUGIN_OPTION_TRACKING_URL, null))
        );
    }

    /**
     * Register module actions.
     */
    public function register_actions()
    {
        // tracking code
        add_action('wp_footer', [$this, 'footer_action']);

        // category page
        add_action('woocommerce_archive_description', [$this, 'category_action']);

        // product page
        add_action('woocommerce_after_single_product', [$this, 'product_action']);

        // checkout page
        add_action('woocommerce_thankyou', [$this, 'checkout_action']);

        // profitshare feed event
        add_action('profitshare_feed_event', [$this, 'feed_action']);

        // profitshare cron feed
        if (!wp_next_scheduled('profitshare_feed_event')) {
            wp_schedule_event(time(), 'daily', "profitshare_feed_event");
        }

        // profitshare verify currency event
        add_action('profitshare_exchange_event', [$this, 'verify_currency']);

        // profitshare cron exchange
        if (!wp_next_scheduled('profitshare_exchange_event')) {
            wp_schedule_event(time(), 'daily', "profitshare_exchange_event");
        }
    }

    /*
     * Generate profitshare feed csv
     */
    public function feed_action()
    {
        (new PWA_Feed())->save();
    }

    /**
     * Generate tracking script and include it in footer .
     */
    public function footer_action()
    {
        $this->profitshare->generateTrackingScript(get_option(self::PLUGIN_OPTION_TRACKING_URL, null));
        $this->profitshare->getTrackingScript();
    }

    /**
     * Generate user profiling script for category page
     */
    public function category_action()
    {
        global $wp_query;
        $categoryId = $wp_query->get_queried_object_id();
        $category = new PWA_Category($categoryId);

        if (empty($category)) {
            return;
        }
        ?>

        <script type="text/javascript">
            var _ps_tgt = {
                a: "<?php echo $this->settings->getTrackingCode();?>",
                pc: "",
                pp: "<?php echo $category->getAveragePrice();?>",
                cc: "<?php echo $category->getId();?>"
            };
            (function () {
                var s = document.createElement("script");
                s.type = "text/javascript";
                s.async = "async";
                s.src = "//<?php echo $this->profitshare->getServer();?>/tgt/js";
                document.body.appendChild(s);
            })();
        </script>

        <style>
            .proftishare_tgt_img {
                display: block;
            }
        </style>

        <?php
    }

    /**
     * Generate user profiling script for product page
     */
    public function product_action()
    {
        global $product;
        $profitshareProduct = new PWA_Product($product->get_id());
        $profitshareProductArray = (new PWA_Product($product->get_id()))->get();
        ?>

        <script type="text/javascript">
            var _ps_tgt = {
                a: "<?php echo $this->settings->getTrackingCode();?>",
                pc: "<?php echo $profitshareProduct->get('id');?>",
                pp: "<?php echo $profitshareProduct->getSalePrice();?>",
                cc: "<?php echo $profitshareProduct->get('categoryId');?>"
            };
            (function () {
                var s = document.createElement("script");
                s.type = "text/javascript";
                s.async = "async";
                s.src = "//<?php echo $this->profitshare->getServer();?>/tgt/js";
                document.body.appendChild(s);
            })();
        </script>


        <style>
            .proftishare_tgt_img {
                display: block;
            }
        </style>
        <?php
    }

    /**
     * Send current order to profitshare
     */
    public function checkout_action($orderId)
    {
        // get orderdata
        $orderData = new WC_Order($orderId);

        if (empty($orderData)) {
            return;
        }

        // init details array
        $details = [
            'external_reference' => $orderId,
            'click_code' => (isset($_COOKIE[$this->settings->getClickCode()])) ? $_COOKIE[$this->settings->getClickCode()] : "",
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'user_ip' => $_SERVER["REMOTE_ADDR"],
        ];

        $productsCount = [];

        foreach ($orderData->get_items() as $k => $item) {
            $variationId = !empty($item['variation_id']) ? $item['variation_id'] : null;

            $product = new PWA_Product($item['product_id'], $variationId);

            if (empty($product->get())) {
                continue;
            }

            $vatValue = (get_option(PWA_Plugin::PLUGIN_OPTION_VAT_KEY, 0) / 100) + 1;

            $productsCount[$product->get('id')] = (isset($productsCount[$product->get('id')])) ? ($productsCount[$product->get('id')] + 1) : 1;

            $details['product_code'][] = $product->get('id') . "_" . $productsCount[$product->get('id')];
            $details['product_part_no'][] = (empty($product->get('model'))) ? $product->get('id') : $product->get('model');
            $details['product_price'][] = number_format($product->getSalePrice() / $vatValue, 4, '.', '');
            $details['product_name'][] = $product->get('name');
            $details['product_link'][] = $product->get('link');
            $details['product_category'][] = $product->get('categoryId');
            $details['product_category_name'][] = $product->get('categoryName');
            $details['product_brand_code'][] = $product->get('manufacturer_id');
            $details['product_brand'][] = $product->get('manufacturer');
            $details['product_qty'][] = $item['qty'];
        }

        $queryString = http_build_query($details);
        $encryptedParams = $this->profitshare->encrypt($queryString, $this->settings->getEncryptionKey());
        $url = "https://{$this->getTrackingUrl()}/ca/0/{$this->settings->getTrackingCode()}/p/$encryptedParams";
        // send order to profitshare via server
        $this->profitshare->callURL($url);

        // send order to profitshare via img
        ?>
        <img src="<?php echo $url; ?>" alt="" border=""
             width="1" height="1" style="border:none !important; margin:0px !important;"/>

        <?php
    }

    /**
     * Get tracking url, used for Romania subdomain [eg. for romanian orders, the url is c.profitshare but for bg is profitshare simple, without subdomain
     *
     * @return string
     */
    private function getTrackingUrl()
    {
        $server = $this->profitshare->getServer();

        if ($server == PWA_Core::DEFAULT_SERVER) {
            $server = "c." . $server;
        }

        return $server;
    }

    /**
     * Add plugin item in wordpress admin menu.
     */
    public function register_settings_page()
    {
        add_menu_page('Profitshare WooCommerce', 'Profitshare WooCommerce', 'edit_others_posts',
            'ps_woocommerce_account_settings', [$this, 'settings_action'], 'dashicons-chart-pie', 21);
        //add_submenu_page('ps_woocommerce_account_settings', 'Asociere categorii', 'Asociere categorii', 'edit_others_posts', 'ps_woocommerce_categories', array($this, 'categories_action'));
    }

    /**
     * Return plugin settings.
     */
    public function settings_action()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validate_module_settings();
        }

        $this->build_module_settings_template();
    }

    /**
     * Return categories page
     */
    public function categories_action()
    {
        // TODO: find a way to load categories template
    }

    /**
     * Update wordpress profitshare categories
     */
    private function update_categories()
    {
        if (!$this->is_plugin_active()) {
            return;
        }

        // validate data
        if (!isset($_POST['wordpressCategoryId']) && !isset($_POST['profitshareCategoryId']) && !isset($_POST['profitshareCategoryCommission'])) {
            die(json_encode(
                [
                    'success' => 0,
                    'message' => $this->trans("Invalid data!"),
                ]
            ));
        }

        $categoryCommission = (int)$_POST['profitshareCategoryCommission'];
        $profitshareCategoryId = (int)$_POST['profitshareCategoryId'];
        $wordpressCategoryId = (int)$_POST['wordpressCategoryId'];

        $categoryValidator = new PWA_Wordpress_Category_Validator($wordpressCategoryId, $profitshareCategoryId,
            $categoryCommission);

        if (!$categoryValidator->isValid()) {
            die(json_encode(
                [
                    'success' => 0,
                    'errors' => $categoryValidator->getErrors(),
                ]
            ));
        }

        $insert = (new PWA_Wordpress_Categories_Model())->add($profitshareCategoryId, $wordpressCategoryId,
            $categoryCommission);

        if (!$insert) {
            die(json_encode(
                [
                    'success' => 0,
                    'message' => $this->trans("Invalid data!"),
                ]
            ));
        }

        // suuccess
        die(json_encode(
            [
                'success' => 1,
            ]
        ));
    }

    /**
     * Build settings HTML.
     */
    private function build_module_settings_template()
    {
        $isFeedFileGenerated = false;
        $this->settings = new PWA_Settings();

        $feedFileName = $this->settings->getFeedFileName();
        $serverCurrency = $this->settings->getServerCurrency();

        if ($this->is_plugin_active()) {
            $this->verify_currency();
        }

        $currencyExchangeValue = $this->settings->getExchangeValue();

        if ($this->settings->getFeedFilePath() && file_exists($this->settings->getFeedFilePath()) && empty($_POST['advertiser_tracking_code'])) {
            $isFeedFileGenerated = true;
        }

        $values = [
            'profitshare_server' => isset($_POST['profitshare_server']) ? $_POST['profitshare_server'] : $this->profitshare->getServer(),
            'advertiser_tracking_code' => isset($_POST['advertiser_tracking_code']) ? $_POST['advertiser_tracking_code'] : $this->settings->getTrackingCode(),
            'advertiser_encryption_key' => isset($_POST['advertiser_encryption_key']) ? $_POST['advertiser_encryption_key'] : $this->settings->getEncryptionKey(),
            'advertiser_vat_value' => (isset($_POST['advertiser_vat_value']) ? $_POST['advertiser_vat_value'] : $this->settings->getVatValue()) . '%',
            'profitshare_feed_file_name' => $feedFileName,
        ];

        $adminSettingsCSSUpdatedAt = filemtime($this->getAssetsPath("assets/css/admin_settings.css"));
        $adminSettingsJSUpdatedAt = filemtime($this->getAssetsPath("assets/js/admin_settings.js"));
        ?>
        <link rel="stylesheet" type="text/css"
              href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
        <link rel="stylesheet" type="text/css"
              href="<?php echo $this->getAssetsUrl("css/admin_settings.css"); ?>?v=<?php echo $adminSettingsCSSUpdatedAt; ?>">
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css"
              integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU"
              crossorigin="anonymous">

        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/js/bootstrap.min.js"></script>

        <div class="container ps-container">
            <div class="row">
                <div class="col-md-12">
                    <div class="row">
                        <div class="col-md-6 col-md-push-1 col-xs-12">
                            <div class="row">
                                <div class="col-md-6 col-xs-12">
                                    <div class="ps-logo-header">
                                        <a href="//<?php echo $this->profitshare->getServer(); ?>" target="_blank"><img
                                                    src="https://profitshare.ro/assets/img/logos/_logo-menu-profitshare.svg"
                                                    alt="Profitshare"></a>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12">
                                    <?php if (isset($this->errors['general']) && !empty($this->errors['general'])): ?>
                                        <?php foreach ($this->errors['general'] as $error): ?>
                                            <div class="alert alert-danger">
                                                <?php echo $error; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                    <?php if (empty($this->errors) && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                                        <div class="alert alert-success">
                                            The settings have been saved and the module has been enabled!
                                        </div>
                                    <?php endif; ?>
                                    <form method="POST">
                                        <div class="row">
                                            <div class="col-xs-12">
                                                <div class="form-group <?php echo (isset($this->errors['profitshare_server'])) ? 'has-error' : ''; ?>">
                                                    <label for="profitshare_server">Profitshare server</label>
                                                    <select class="form-control" id="profitshare_server"
                                                            name="profitshare_server">
                                                        <?php foreach (PWA_Core::SERVERS as $server): ?>
                                                            <option <?php echo ($values['profitshare_server'] == $server) ? 'selected' : ''; ?>
                                                                    value="<?php echo $server; ?>"><?php echo $server; ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <?php if (isset($this->errors['profitshare_server'])): ?>
                                                        <div class="alert alert-danger">
                                                            <?php echo $this->errors['profitshare_server']; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-xs-12 col-xs-6">
                                                <div class="form-group <?php echo (isset($this->errors['advertiser_tracking_code'])) ? 'has-error' : ''; ?>">
                                                    <label for="advertiser_tracking_code">Advertiser tracking
                                                        code</label>
                                                    <input value="<?php echo $values['advertiser_tracking_code']; ?>"
                                                           type="text" class="form-control"
                                                           id="advertiser_tracking_code"
                                                           name="advertiser_tracking_code">
                                                    <?php if (isset($this->errors['advertiser_tracking_code'])): ?>
                                                        <div class="alert alert-danger">
                                                            <?php echo $this->errors['advertiser_tracking_code']; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="col-xs-12 col-xs-6">
                                                <div class="form-group <?php echo (isset($this->errors['advertiser_encryption_key'])) ? 'has-error' : ''; ?>">
                                                    <label for="advertiser_encryption_key">Encryption key</label>
                                                    <input value="<?php echo $values['advertiser_encryption_key']; ?>"
                                                           type="text" class="form-control"
                                                           id="advertiser_encryption_key"
                                                           name="advertiser_encryption_key">
                                                    <?php if (isset($this->errors['advertiser_encryption_key'])): ?>
                                                        <div class="alert alert-danger">
                                                            <?php echo $this->errors['advertiser_encryption_key']; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-xs-12">
                                                <div class="form-group <?php echo (isset($this->errors['advertiser_vat_value'])) ? 'has-error' : ''; ?>">
                                                    <label for="advertiser_vat_value">VAT value</label>
                                                    <input value="<?php echo $values['advertiser_vat_value']; ?>"
                                                           type="text" class="form-control" id="advertiser_vat_value"
                                                           name="advertiser_vat_value">
                                                    <?php if (isset($this->errors['advertiser_vat_value'])): ?>
                                                        <div class="alert alert-danger">
                                                            <?php echo $this->errors['advertiser_vat_value']; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-xs-12">
                                                <div class="row">
                                                    <div class="col-md-9 col-xs-12">
                                                        <div class="feed-link">
                                                            <?php if (!empty($values['profitshare_feed_file_name']) && $isFeedFileGenerated): ?>
                                                                <span>Here's your</span> <span id="feedFileName"
                                                                                               data-toggle="tooltip"
                                                                                               data-placement="top"
                                                                                               title="Copy to clipboard"
                                                                                               class="green-text"
                                                                                               value="<?php echo $values['profitshare_feed_file_name']; ?>">profitshare feed link.</span>
                                                                <div class="clear-class"></div>
                                                                <div class="link-copied" id="linkCopied">
                                                                    <i class="fas fa-check-circle"></i><span>Copied to clipboard.</span>
                                                                </div>
                                                            <?php elseif (((!empty($_POST['advertiser_tracking_code'])) || $isFeedFileGenerated == false && !empty($feedFileName)) && empty($this->errors)): ?>
                                                                <p>We are working on to generate your csv products feed,
                                                                    please try again in a few minutes.</p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3 col-xs-12">
                                                        <div class="buttons">
                                                            <button type="submit"
                                                                    class="btn btn-ps btn-block btn-primary">Save
                                                            </button>
                                                            <!--<a  class="btn btn-success col-md-3">Asociere categorii</a>-->
                                                        </div>
                                                        <div class="buttons">
                                                            <a href="//support.<?php echo $this->profitshare->getServer(); ?>"
                                                               target="_blank" class="grey-link">Contact us</a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-xs-12">
                                                        <?php if ($currencyExchangeValue != 1 && get_option("woocommerce_currency") != $this->settings->getServerCurrency()): ?>
                                                            <div class="exchange-details">
                                                                <p>We are converting prices from your currency to
                                                                    <b><?php echo $serverCurrency; ?></b> based on <b><a
                                                                                href="https://exchangeratesapi.io/"
                                                                                target="_blank">https://exchangeratesapi.io/</a></b>
                                                                    service. On <b><?php echo date('d.m.Y'); ?></b>
                                                                    1 <?php echo get_option("woocommerce_currency"); ?>
                                                                    = <?php echo $currencyExchangeValue; ?> <?php echo $serverCurrency; ?>
                                                                    .</p>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <script src="<?php echo $this->getAssetsUrl("js/admin_settings.js"); ?>?v=<?php echo $adminSettingsJSUpdatedAt; ?>"></script>
        <?php
    }

    /**
     * Validate inputs from module settings.
     */
    private function validate_module_settings()
    {
        // validate server
        if (!isset($_POST['profitshare_server']) || !in_array($_POST['profitshare_server'], PWA_Core::SERVERS)) {
            $this->errors['profitshare_server'] = "Invalid profitshare server!";
        }

        // validate advertiser tracking code
        if (!isset($_POST['advertiser_tracking_code']) || strlen($_POST['advertiser_tracking_code']) < 10) {
            $this->errors['advertiser_tracking_code'] = "Invalid advertiser tracking code!";
        }

        // validate advertiser encryption key
        if (!isset($_POST['advertiser_encryption_key']) || strlen($_POST['advertiser_encryption_key']) < 10) {
            $this->errors['advertiser_encryption_key'] = "Invalid encryption key!";
        }

        // validate vat value
        $_POST['advertiser_vat_value'] = (int)(isset($_POST['advertiser_vat_value']) ? $_POST['advertiser_vat_value'] : 0);

        if ($_POST['advertiser_vat_value'] < 0 || $_POST['advertiser_vat_value'] > 100) {
            $this->errors['advertiser_vat_value'] = "Invalid advertiser vat value, it must be between 0 and 100";
        }
        // end of validate vat value

        // get tracking url and click code if there are no errors
        if (empty($this->errors)) {

            $this->profitshare->advertiserDetailsUrl = sprintf($this->profitshare->advertiserDetailsUrl,
                $_POST['profitshare_server']);

            if ($_POST['profitshare_server'] == PWA_Core::SERVERS[1]) {
                $this->profitshare->advertiserDetailsUrl = str_replace("app.", "",
                    $this->profitshare->advertiserDetailsUrl);
            }

            $state = "profitshare";

            $postData = [
                'key' => $this->profitshare->encrypt($state, $_POST['advertiser_encryption_key']),
                'tracking_code' => $_POST['advertiser_tracking_code'],
                'state' => $state,
            ];

            $result = json_decode($this->profitshare->callURL($this->profitshare->advertiserDetailsUrl, $postData),
                true);

            if (empty($result)) {
                $this->errors['general'][] = "Invalid request, please try again or contact profitshare suuport!";
            }

            if (isset($result['error'])) {
                $this->errors['general'][] = $result['error'];
            }

            if (isset($result['trackingUrl'])) {
                update_option(self::PLUGIN_OPTION_TRACKING_URL, $result['trackingUrl']);
            }

            if (isset($result['clickCode'])) {
                update_option(self::PLUGIN_OPTION_CLICK_CODE, $result['clickCode']);
            }
        }

        // generate feed file name
        $feedFileName = md5(time()) . ".csv";

        // if there are still no errors, then update plugin options
        if (empty($this->errors)) {
            // deactivate crons
            $this->deactivate_crons();

            // verify store currency
            $this->verify_currency();

            update_option(self::PLUGIN_OPTION_COUNTRY, $_POST['profitshare_server']);
            update_option(self::PLUGIN_OPTION_ADVERTISER_KEY, $_POST['advertiser_tracking_code']);
            update_option(self::PLUGIN_OPTION_ENCRYPTION_KEY, $_POST['advertiser_encryption_key']);
            update_option(self::PLUGIN_OPTION_VAT_KEY, $_POST['advertiser_vat_value']);
            update_option(self::PLUGIN_OPTION_FEED_FILE_NAME, $feedFileName);
        }
    }

    /**
     * verify store currency, if current store is different by our default currency then we will check the exchange value using exchangeratesapi.io
     */
    public function verify_currency()
    {
        $storeCurrency = get_option("woocommerce_currency");

        if (empty($storeCurrency)) {
            return update_option(self::PLUGIN_OPTION_EXCHANGE_VALUE, self::PLUGIN_OPTION_DEFAULT_EXCHANGE_VALUE);
        }

        if ($storeCurrency === self::DEFAULT_CURRENCY) {
            return update_option(self::PLUGIN_OPTION_EXCHANGE_VALUE, self::PLUGIN_OPTION_DEFAULT_EXCHANGE_VALUE);
        }

        try {
            $exchangeRatesAPI = new ExchangeRatesAPI($storeCurrency);
            $exchangeValue = $exchangeRatesAPI->getRate($this->settings->getServerCurrency());
        } catch (Exception $e) {
            // @TODO: save errors
        }

        if (empty($exchangeValue) || !is_numeric($exchangeValue)) {
            return update_option(self::PLUGIN_OPTION_EXCHANGE_VALUE, self::PLUGIN_OPTION_DEFAULT_EXCHANGE_VALUE);
        }

        update_option(self::PLUGIN_OPTION_EXCHANGE_VALUE, number_format($exchangeValue, 4, '.', ''));

        // reset settings
        $this->settings = new PWA_Settings();
    }

    /**
     * Require import classes for plugin feed.
     */
    public function require_classes()
    {
        // require profitshare model
        require_once($this->includes_path . 'models/model-PWA.php');
        require_once($this->includes_path . 'validators/validator-PWA.php');

        foreach ($this->rglob($this->includes_path . '*.php') as $file) {
            if (strpos($file, "templates") !== false) {
                continue;
            }

            require_once($file);
        }
    }

    /**
     * Recursive php glob
     *
     * @param $pattern
     * @param int $flags
     * @return array
     */
    private function rglob($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);
        foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge($files, $this->rglob($dir . '/' . basename($pattern), $flags));
        }
        return $files;
    }
}