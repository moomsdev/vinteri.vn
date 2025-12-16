<?php
namespace App\Settings;

use Gaumap\Helpers\GauMap;
use PHPMailer\PHPMailer\PHPMailer;

class ThemeSettings {
    public function __construct() {
        $this->useGmailSmtp();
        $this->hookAfterLogout();
        // $this->createRequiredPages();
        $this->hideAdminBar();
        $this->AddActiveClassToCurrentMenu();
        // $this->addHeaderData();
        $this->addFooterData();
    }

    public function useGmailSmtp() {
        if (get_option('_use_smtp') === 'yes') {
            add_action('phpmailer_init', static function (PHPMailer $phpmailer) {
                $phpmailer->isSMTP();
                $phpmailer->Host       = get_option('_smtp_host');
                $phpmailer->SMTPAuth   = true;
                $phpmailer->SMTPSecure = get_option('_smtp_secure');
                $phpmailer->Port       = get_option('_smtp_port');
                $phpmailer->Username   = get_option('_smtp_username');
                $phpmailer->Password   = get_option('_smtp_password');
                $phpmailer->From       = get_option('_admin_email');
                $phpmailer->FromName   = get_bloginfo('name');
            });
        }
    }

    public function hookAfterLogout() {
        add_action('wp_logout', function () {
            updateUserMeta(get_current_user_id(), 'last_login', '');
            wp_redirect(home_url());
            exit();
        });
    }

    public function createRequiredPages() {
        // Create page, search, 404, header, footer
        $pages = [
            'page',
            'search',
        ];

        foreach ($pages as $page) {
            $filename = __DIR__ . '/../../../' . $page . '.php';
            if (!file_exists($filename)) {
                file_put_contents($filename, '<?php get_header() ?>
<?php theBreadcrumb() ?>
<?php get_footer() ?>');
            }
        }

        $page404 = __DIR__ . '/../../../404.php';
        if (!file_exists($page404)) {
            file_put_contents($page404, '<?php get_header() ?>
<?php theBreadcrumb() ?>
<div class="container">
    <img src="<?php echo get_template_directory_uri() . "/resources/images/404.png" ?>" alt="404">
</div>
<?php get_footer() ?>');
        }

        $filename = __DIR__ . '/../../../header.php';
        if (!file_exists($filename)) {
            file_put_contents($filename, '<!doctype html>
<html lang="en">
<head>
    <?php wp_head() ?>
</head>
<body <?php body_class() ?>>');
        }

        $filename = __DIR__ . '/../../../footer.php';
        if (!file_exists($filename)) {
            file_put_contents($filename, '<?php wp_footer() ?></body></html>');
        }

        $filename = __DIR__ . '/../../../sidebar.php';
        if (!file_exists($filename)) {
            file_put_contents($filename, '');
        }
    }

    public function hideAdminBar() {
        if (is_user_logged_in()) {
            show_admin_bar(true);
            add_filter('show_admin_bar', '__return_true');
        } else {
            show_admin_bar(false);
            add_filter('show_admin_bar', '__return_false');
        }
    }

    /**
     * Thêm class active vào current menu
     *
     * @param $classes
     *
     * @return array
     */
    public function AddActiveClassToCurrentMenu() {
        add_filter('nav_menu_css_class', static function ($classes) {
            if (in_array('current-menu-item', $classes, true)) {
                $classes[] = 'current-menu-item';
            }
            return $classes;
        }, 10, 2);
    }

    public function addHeaderData() {
        add_action('wp_head', static function () {
            $faviconUrl = wp_get_attachment_image_url(getOption('favicon'));
            $title      = wp_title('&raquo;', false);
            $obj        = get_queried_object();
            if ($obj instanceof \WP_Term) {
                $description = $obj->description;
                $image       = '';
            } elseif ($obj instanceof \WP_Post) {
                if (has_excerpt($obj->ID)) {
                    $description = getExcerpt($obj->ID, 160);
                }
                $image = getPostThumbnailUrl($obj->ID, 1200, 628);
            } else {
                $description = get_bloginfo('description');
                $image       = '';
            }

            echo '<meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
                    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
                    <meta name="author" content="' . AUTHOR['name'] . '" />
                    <meta name="copyright" content="' . AUTHOR['name'] . ' [' . AUTHOR['email'] . '] [' . AUTHOR['website'] . ']" />
                    <meta http-equiv="Content-Language" content="' . get_bloginfo('language') . '"/>
                    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
                    <link rel="icon" href="' . $faviconUrl . '" type="image/x-icon"/>
                    <link rel="shortcut icon" href="' . $faviconUrl . '" type="image/x-icon" />
                    <link rel="apple-touch-icon" href="' . $faviconUrl . '" type="image/x-icon" />
                    <!--[if lt IE 9]>
                    <script src="//oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
                    <script src="//oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
                    <![endif]-->';
            echo carbon_get_theme_option('header_scripts');
        }, PHP_INT_MAX);
    }

    public function addFooterData() {
        add_action('wp_footer', static function () {
            echo carbon_get_theme_option('footer_scripts');
        }, PHP_INT_MAX);
    }

    /**
     * Load custom path javascript file
     *
     * @param $files
     */
    public function LoadCustomJavascriptFile($files) {
        $count = 1;
        wp_enqueue_script('mooms-google-map', 'https://maps.googleapis.com/maps/api/js?key=' . apply_filters('carbon_fields_map_field_api_key', true) . '&libraries=geometry,places,drawing', [], '0.1.0', true);

        foreach ($files as $file) {
            $scriptHandle = 'gaumap-css-' . $count;
            wp_enqueue_script($scriptHandle, $file, [], '0.1.0', true);
            $count++;
        }
    }

    /**
     * Load custom path css file
     *
     * @param $files
     */
    public function loadCustomStyleSheetFiles($files) {
        $count = 1;
        foreach ($files as $file) {
            wp_enqueue_style('gaumap-css-' . $count, $file, [], '0.1.0');
            $count++;
        }

        wp_enqueue_style($count, get_stylesheet_directory_uri() . '/style.css', [], '0.1.0');
    }
}
