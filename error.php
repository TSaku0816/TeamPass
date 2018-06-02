<?php
/**
 * Teampass - a collaborative passwords manager
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @category  Teampass
 * @package   Error.php
 * @author    Nils Laumaillé <nils@teampass.net>
 * @copyright 2009-2018 Nils Laumaillé
 * @license   GNU GPL-3.0
 * @version   GIT: <git_id>
 * @link      http://www.teampass.net
 */


if (file_exists('../sources/SecureHandler.php')) {
    include_once '../sources/SecureHandler.php';
} elseif (file_exists('./sources/SecureHandler.php')) {
    include_once './sources/SecureHandler.php';
} else {
    throw new Exception("Error file '/sources/SecureHandler.php' not exists", 1);
}
if (!isset($_SESSION)) {
    session_start();
}
if (!isset($_SESSION['CPM']) || $_SESSION['CPM'] != 1) {
    die('Hacking attempt...');
}

// Load config
if (file_exists('../includes/config/tp.config.php')) {
    include_once '../includes/config/tp.config.php';
} elseif (file_exists('./includes/config/tp.config.php')) {
    include_once './includes/config/tp.config.php';
} else {
    throw new Exception("Error file '/includes/config/tp.config.php' not exists", 1);
}

if (null !== filter_input(INPUT_POST, 'session', FILTER_SANITIZE_STRING)
    && filter_input(INPUT_POST, 'session', FILTER_SANITIZE_STRING) === "expired"
) {
    //Include files
    include_once $SETTINGS['cpassman_dir'].'/includes/config/settings.php';
    include_once $SETTINGS['cpassman_dir'].'/includes/config/include.php';
    include_once $SETTINGS['cpassman_dir'].'/sources/SplClassLoader.php';

    // connect to DB
    include_once $SETTINGS['cpassman_dir'].'/includes/libraries/Database/Meekrodb/db.class.php';
    DB::$host         = DB_HOST;
    DB::$user         = DB_USER;
    DB::$password     = defuse_return_decrypted(DB_PASSWD);
    DB::$dbName       = DB_NAME;
    DB::$port         = DB_PORT;
    DB::$encoding     = DB_ENCODING;
    $link = mysqli_connect(DB_HOST, DB_USER, defuse_return_decrypted(DB_PASSWD), DB_NAME, DB_PORT);
    $link->set_charset(DB_ENCODING);

    // Include main functions used by TeamPass
    include_once 'sources/main.functions.php';

    // Update table by deleting ID
    if (isset($_SESSION['user_id'])) {
        DB::update(
            $pre."users",
            array(
                'key_tempo' => ''
            ),
            "id=%i",
            $_SESSION['user_id']
        );
    }

    //Log into DB the user's disconnection
    if (isset($SETTINGS['log_connections']) && $SETTINGS['log_connections'] == 1) {
        logEvents('user_connection', 'disconnection', $_SESSION['user_id'], $_SESSION['login']);
    }
} else {
    include_once $SETTINGS['cpassman_dir'].'/sources/main.queries.php';
    $errorCode = '';
    if (@$_SESSION['error']['code'] === ERR_NOT_ALLOWED) {
        $errorCode = langHdl('error_not_authorized');
    } elseif (@$_SESSION['error']['code'] === ERR_NOT_EXIST) {
        $errorCode = langHdl('error_not_exists');
    } elseif (@$_SESSION['error']['code'] === ERR_SESS_EXPIRED) {
        $errorCode = langHdl('index_session_expired');
    } elseif (@$_SESSION['error']['code'] === ERR_VALID_SESSION) {
        $errorCode = langHdl('error_not_authorized');
    }
?>
<!-- Main content -->
<section class="content">
      <div class="error-page">
        <h2 class="headline text-warning"> 404</h2>

        <div class="error-content">
          <h3><i class="fa fa-warning text-warning"></i> Oops! <?php echo $errorCode;?>.</h3>

          <p>
            We could not find the page you were looking for.
            Meanwhile, you may <a href="index.php">return to home</a>.
          </p>

        </div>
        <!-- /.error-content -->
      </div>
      <!-- /.error-page -->
    </section>
    <!-- /.content -->
<?php
}

// erase session table
$_SESSION = array();

// Kill session
session_destroy();

?>
