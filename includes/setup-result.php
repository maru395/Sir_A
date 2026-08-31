<?php
// Used by database/createdb.php and database/populatedb.php to display setup results.
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?> · AviTON</title>
        <link rel="stylesheet" href="../assets/vendor/sb-admin-2/css/sb-admin-2.min.css">
    </head>
    <body class="bg-light">
        <main class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-7">
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <p class="text-primary font-weight-bold">
                                AviTON · Local setup
                            </p>
                            <h1 class="h3">
                                <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
                            </h1>
                            <p role="status">
                                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                            </p>
                            <ol class="pl-3">
                            <li>
                                <a href="createdb.php">Create database and install schema.sql</a>
                            </li>
                            <li>
                                <a href="populatedb.php">Populate sample accounts and equipment</a>
                            </li>
                            </ol>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <caption>
                                        Sample logins after population succeeds
                                    </caption>
                                    <thead>
                                        <tr>
                                            <th>
                                                Username
                                            </th>
                                            <th>
                                                Password
                                            </th>
                                            <th>
                                                Role
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                Admin
                                            </td>
                                            <td>
                                                Admin
                                            </td>
                                            <td>
                                                ADMIN
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                User
                                            </td>
                                            <td>
                                                User
                                            </td>
                                            <td>
                                                USER
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p class="small">
                                These are local demo passwords. Change both in Account before sharing the site. Passwords are stored as hashes. Refreshing keeps existing rows. Population adds missing samples without resetting passwords or stock.
                            </p>
                            <a class="btn btn-primary" href="../index.php">Open sign in</a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </body>
</html>
