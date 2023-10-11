<?

/**
 * Plugin Name:       kipdev_auberge_reservation
 * Description:       manage a small guest house.
 * Version:           1.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Kipdev wp solutions (Dennis K)
 * Author URI:        https://denniskip.com/
 * License:           GPL v2 or later
 **/
if (!defined('ABSPATH')) {
    exit;
}

function my_plugin_enqueue_scripts()
{
    // Get the URL of the CSS file
    $css_url = plugins_url('kipdev_style.css', __FILE__);

    // Enqueue the CSS file
    wp_enqueue_style('my-plugin-style', $css_url);
}
add_action('wp_enqueue_scripts', 'my_plugin_enqueue_scripts');

function my_plugin_scripts()
{
    wp_enqueue_script('jquery');
    wp_enqueue_script('my-plugin-script', plugin_dir_url(__FILE__) . 'jquery-script.js', array('jquery'));
}
add_action('wp_enqueue_scripts', 'my_plugin_scripts');

function reservation_filter($id_reservation)
{
    $output = '
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    ';
    $current_page_id = get_the_ID();
    $action = "/reservation/";

    // Define who is doing the reservation
    if (is_user_logged_in()) {
        $today = date('Y-m-d');
        $current_user = wp_get_current_user();
        $cree_par = $current_user->user_login;
    } else {
        $now = date('Y-m-d');
        $today = date('Y-m-d', strtotime('+2 days', strtotime($now)));
    }

    // Get the form input values
    $checkin_date = isset($_POST['checkin_date']) ? $_POST['checkin_date'] : '';
    $checkout_date = isset($_POST['checkout_date']) ? $_POST['checkout_date'] : '';
    $nombre_enfants = isset($_POST['nombre_enfants']) ? $_POST['nombre_enfants'] : '';
    $nombre_adultes = isset($_POST['nombre_adultes']) ? $_POST['nombre_adultes'] : '';

    // Form to filter beds
    $output .= '<div class="container">';
    //$output .= '<h3>Le passage à l\'auberge est réservé aux adhérents. Si vous n\'avez pas encore adhéré, commencez par ici :  <a href="/gestions-des-adherents/">Devenir un adhérent.</a> </h3><br>';
    $output .= '<form action="' . $action . '" method="post">';
    $output .= '<div class="row">';
    $output .= '<div class="col-md">';
    $output .= '<label for="checkin_date">Date d\'arrivée:</label>';
    $output .= '<input type="date" id="datePicker" name="checkin_date" min="' . $today . '" value="' . $checkin_date . '">';
    $output .= '</div>';
    $output .= '<div class="col-md">';
    $output .= '<label for="checkout_date">Date de départ:</label>';
    $output .= '<input type="date" id="datePicker" name="checkout_date" min="' . $today . '" value="' . $checkout_date . '">';
    $output .= '</div>';
    $output .= '<div class="col-md">';
    $output .= '<label for="nombre_enfants">Nombre d\'enfants:</label>';
    $output .= '<input type="number" id="nombre_enfants" name="nombre_enfants" min="0" value="0">';
    $output .= '</div>';
    $output .= '<div class="col-md">';
    $output .= '<label for="nombre_adultes">Nombre d\'adultes:</label>';
    $output .= '<input type="number" id="nombre_adultes" name="nombre_adultes" min="1" value="' . $nombre_adultes . '" required>';
    $output .= '</div>';
    $output .= '</div>';
    $output .= '<input type="submit" value="Voir les disponibilités" class="btn btn-primary" name="btn_availability">';
    $output .= '</form>';
    $output .= '</div>';

    if (isset($_POST['btn_availability'])) {

        // Calculate the number of requested beds
        $nombre_place = $nombre_enfants + $nombre_adultes;
        //echo "nombre_place " . $nombre_place;

        // Create an array with all the dates in the range
        $dates = array();
        $current_date = strtotime($checkin_date);
        $last_date = strtotime($checkout_date);
        while ($current_date < $last_date) {
            $dates[] = date('Y-m-d', $current_date);
            $current_date = strtotime('+1 day', $current_date);
        }
        print_r($dates);
        $counter = count($dates);
        $adhesion_cost = 0;


        //query to get available beds
        $sql = "SELECT l.id_lit, l.id_dortoir, d.nom AS nom_dortoir, l.numero_lit, l.type_lit
        FROM kipdev_auberge_lits l
        JOIN kipdev_auberge_dortoirs d ON l.id_dortoir = d.id_dortoir
        WHERE l.disponible = true
        AND l.id_lit NOT IN (
            SELECT rd.id_lit
            FROM kipdev_auberge_reservation_details rd
            INNER JOIN kipdev_auberge_reservations r ON rd.id_reservation = r.id_reservation
            WHERE r.statut != 'Annulée' AND (
                (r.date_arrivee >= '$checkin_date' AND r.date_arrivee <'$checkout_date') OR
                (r.date_depart > '$checkin_date' AND r.date_depart <= '$checkout_date') OR
                (r.date_arrivee < '$checkin_date' AND r.date_depart > '$checkout_date')
            )
        )
        GROUP BY l.id_lit, l.id_dortoir, d.nom, l.numero_lit, l.type_lit
        ";

        try {
            $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASSWORD);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $conn->prepare($sql);

            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($result) {
                $output .= '<form method="post" action="/reservation/">';
                // Add dates
                $output .= '<input type="hidden" name="checkin_date_hidden" value="' . $checkin_date . '">';
                $output .= '<input type="hidden" name="checkout_date_hidden" value="' . $checkout_date . '">';
                // Add other variables
                $output .= '<input type="hidden" name="nombre_place" value="' . $nombre_place . '">';
                $output .= '<input type="hidden" name="nombre_nuit" value="' . $counter . '">';
                
                $output .= '<div class="alert alert-success" role="alert">';
                $output .= 'Nous avons ' . count($result) . ' lit(s) disponible(s) pour vous ! Sélectionnez ' . $nombre_place . ' lits';
                $output .= '</div>';
                $output .= '<table class="table">';
                $output .= '<thead>';
                $output .= '<tr>';
                $output .= '<th scope="col">Numéro de lit</th>';
                $output .= '<th scope="col">Type de lit</th>';
                $output .= '<th scope="col">Dortoir</th>';
                $output .= '<th scope="col">Action</th>';
                $output .= '</tr>';
                $output .= '</thead>';
                $output .= '<tbody>';
                
                foreach ($result as $row) {
                    $output .= '<tr>';
                    $output .= '<td>' . $row['numero_lit'] . '</td>';
                    $output .= '<td>' . $row['type_lit'] . '</td>';
                    $output .= '<td><a href="/les-hebergements/">' . $row['nom_dortoir'] . '</a></td>';
                    $output .= '<td><input type="checkbox" name="selected_beds[]" value="' . $row['id_lit'] . '" onclick="limitSelections(' . $nombre_place . ')" max="' . $nombre_place . '"> Réserver</td>';
                    $output .= '</tr>';
                }
                
                $output .= '</tbody>';
                $output .= '</table>';
                
                // Add JavaScript function to limit the number of selections
                $output .= '<script>
                function limitSelections(maxSelections) {
                    var checkboxes = document.getElementsByName("selected_beds[]");
                    var numChecked = 0;
                    
                    for (var i = 0; i < checkboxes.length; i++) {
                        if (checkboxes[i].checked) {
                            numChecked++;
                        }
                        
                        if (numChecked > maxSelections) {
                            checkboxes[i].checked = false;
                            alert("Vous ne pouvez sélectionner que " + maxSelections + " lits.");
                            break;
                        }
                    }
                }
                </script>';
                
             
                
                // Control the number of beds that can be selected

                $output .= '<br>';
                $output .= '<h4>Êtes-vous un adhérent ?</h4>';
                $output .= '<div class="container">';
                $output .= '<input type="radio" name="iam_adherent" value="yes" onclick="toggleUserForm(true)"> Oui<br>';
                $output .= '<input type="radio" name="iam_adherent" value="no" onclick="toggleUserForm(false)"> Non<br>';
                $output .= '</div>';

                $output .= '<div id="current_user_verif_is_member" style="display: none;">';
                $output .= '<div class="row">';
                $output .= '<div class="col-md">';
                $output .= '<label>Votre mail</label>';
                $output .= '<input type="text" name="adh_email" value="' . $email . '"/>';
                $output .= '</div>';
                $output .= '<div class="col-md">';
                $output .= '<label>Votre identifiant</label>';
                $output .= '<input type="number" name="identifient" value="' . $identifient . '"/>';
                $output .= '</div>';
                $output .= '</div>';
                $output .= '</div>';

                $output .= '<div id="current_user_add_member" style="display: none;">';
                $output .= '<h4>Dévenir un adhérent</h4>';
                $output .= '<div class="row">';
                $output .= '<div class="col-md">';
                $output .= '<label>Prénom</label>';
                $output .= '<input type="text" name="prenom_user" value="' . $prenom . '"/>';
                $output .= '</div>';
                $output .= '<div class="col-md">';
                $output .= '<label>Nom</label>';
                $output .= '<input type="text" name="nom_user" value="' . $nom . '"/>';
                $output .= '</div>';
                $output .= '</div>';
                $output .= '<div class="row">';
                $output .= '<div class="col-md">';
                $output .= '<label>Email</label>';
                $output .= '<input type="email" name="email_user" value="' . $email . '"/>';
                $output .= '</div>';
                $output .= '<div class="col-md">';
                $output .= '<label>Téléphone</label>';
                $output .= '<input type="text" name="telephone_user" value="' . $telephone . '"/>';
                $output .= '</div>';
                $output .= '<div class="col-md">';
                $output .= '<label>Adresse</label>';
                $output .= '<input type="text" name="adresse_user" value="' . $adresse . '"/>';
                $output .= '</div>';
                $output .= '</div>';
                $output .= '<div class="row">';
                $output .= '<div class="col-md">';
                $output .= '<label>Code postal</label>';
                $output .= '<input type="text" name="code_postal_user" value="' . $code_postal . '"/>';
                $output .= '</div>';
                $output .= '<div class="col-md">';
                $output .= '<label>Ville</label>';
                $output .= '<input type="text" name="ville_user" value="' . $ville . '"/>';
                $output .= '</div>';
                $output .= '</div>';
                $output .= '</div>';

                if ($nombre_adultes > 1) {
                    $output .= '<h4>Les autres adultes sont-ils adhérents ?</h4>';
                    $output .= '<div class="container">';
                    $output .= '<input type="radio" name="is_adherent" value="yes" onclick="toggleMemberForm(true)"> Oui<br>';
                    $output .= '<input type="radio" name="is_adherent" value="no" onclick="toggleMemberForm(false)"> Non<br>';
                    $output .= '</div>';
                    
                    $output .= '<div id="formVerifyMember" style="display: none;">';
                    $output .= '<h4>Verification adhérents</h4>';
                    
                    for ($i = 2; $i <= $nombre_adultes; $i++) {
                        $output .= '<div class="row">';
                        $output .= '<div class="col-md">';
                        $output .= '<label>Email de l\'adulte ' . $i . '</label>';
                        $output .= '<input type="text" name="email_adulte[]">';
                        $output .= '</div>';
                        $output .= '<div class="col-md">';
                        $output .= '<label>Identifiant de l\'adulte ' . $i . '</label>';
                        $output .= '<input type="number" name="identifiant_adulte[]">';
                        $output .= '</div>';
                        $output .= '</div>';
                    }
                    $output .= '</div>';

                    $output .= '<div id="formAddMember" style="display: none;">';
                    if ($nombre_adultes == 2) {
                        $output .= '<div>';
                $output .= '<h4>Enregistre 2ème person autant un adhérant</h4>';
                $output .= '<div class="row">';
                $output .= '<div class="col-md">';
                $output .= '<label>Prénom</label>';
                $output .= '<input type="text" name="prenom_personii" value="' . $prenomii . '"/>';
                $output .= '</div>';
                $output .= '<div class="col-md">';
                $output .= '<label>Nom</label>';
                $output .= '<input type="text" name="nom_personii" value="' . $nomii . '"/>';
                $output .= '</div>';
                $output .= '</div>';
                $output .= '<div class="row">';
                $output .= '<div class="col-md">';
                $output .= '<label>Email</label>';
                $output .= '<input type="email" name="email_personii" value="' . $emailii. '"/>';
                $output .= '</div>';
                $output .= '<div class="col-md">';
                $output .= '<label>Téléphone</label>';
                $output .= '<input type="text" name="telephone_personii" value="' . $telephoneii. '"/>';
                $output .= '</div>';
                $output .= '<div class="col-md">';
                $output .= '<label>Adresse</label>';
                $output .= '<input type="text" name="adresse_personii" value="' . $adresseii. '"/>';
                $output .= '</div>';
                $output .= '</div>';
                $output .= '<div class="row">';
                $output .= '<div class="col-md">';
                $output .= '<label>Code postal</label>';
                $output .= '<input type="text" name="code_postal_personii" value="' . $code_postalii. '"/>';
                $output .= '</div>';
                $output .= '<div class="col-md">';
                $output .= '<label>Ville</label>';
                $output .= '<input type="text" name="ville_personii" value="' . $villeii. '"/>';
                $output .= '</div>';
                $output .= '</div>';
                $output .= '</div>';
                    }else {
                        

                        $output .= '<div class="alert alert-success" role="alert"> Merci d’ajouter les adhérents avant de poursuivre votre réservation. Vous aurez besoin de leurs adresses mails.!</div>';
               
                        $output .= '<a href="https://aubergelesbainsdouches.fr/gestions-des-adherents/"> Ajouter les adherents</a>';
                        

                    }
                    $output .= '</div>';
                     
                }
                

                $output .= '<input type="submit" name ="soummetre" value="Réserver">';
                $output .= '</form>';
            } else {
                $output .= '<div class="alert alert-danger" role="alert">';
                $output .= 'Il n\'y a pas de lits disponibles pour les dates sélectionnées.';
                $output .= '</div>';
            } 
        } catch (PDOException $e) {
            echo "error while selecting available beds " . $e;
        }
        $conn = null;
    }
    if (isset($_POST['soummetre'])) {
        $selected = isset($_POST['selected_beds']) ? $_POST['selected_beds'] : array();
        $checkin_date = $_POST['checkin_date_hidden'];
        $checkout_date = $_POST['checkout_date_hidden'];
        $nombre_nuit = $_POST['nombre_nuit'];
        $nombre_place = $_POST['nombre_place'];
        $adhesion_cost = 0;
        $isRedirectionNeeded = false; // Variable to track if redirection is needed

        // Handle form submission based on user selection
        if ($_POST['iam_adherent'] === 'yes') {
            // Verify user
            $email = $_POST['adh_email'];
            $identifient = $_POST['identifient'];

            try {
                $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASSWORD);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $stmt = $conn->prepare("SELECT COUNT(*) FROM kipdev_auberge_adherents WHERE email = :email AND id_adherent = :id_adherent");
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':id_adherent', $identifient);
                $stmt->execute();
                $result = $stmt->fetchColumn();

                if (!$result) {
                    $output .= '<script>';
                    $output .= 'alert("Les informations fournies n\'ont pas permis de trouver un adhérent. Veuillez vérifier votre identifiant et votre email ou vous inscrire pour devenir membre si vous ne l\'êtes pas encore.");';
                    $output .= '</script>';
                } else {
                    $adhesion_cost = 0;
                    $isRedirectionNeeded = true; // Set the variable to indicate successful execution
                }
            } catch (PDOException $e) {
                error_log("Error authenticating member: " . $e->getMessage());
                $output .= '<div class="alert alert-danger" role="alert">';
                $output .= 'An error occurred. Please try again later.';
                $output .= '</div>';
            }
        } elseif ($_POST['iam_adherent'] === 'no') {
            // Add new adherent
            $adhesion_cost += 5;
            $prenom = $_POST['prenom_user'];
            $nom = $_POST['nom_user'];
            $email = $_POST['email_user'];
            $telephone = $_POST['telephone_user'];
            $adresse = $_POST['adresse_user'];
            $code_postal = $_POST['code_postal_user'];
            $ville = $_POST['ville_user'];

            $sql = "INSERT INTO `kipdev_auberge_adherents`(`prenom`, `nom`, `email`, `telephone`, `adresse`, `code_postal`, `ville`, `date_inscription`, `cree_par`) 
                    VALUES (:prenom, :nom, :email, :telephone, :adresse, :code_postal, :ville, CURRENT_TIMESTAMP, :cree_par)";

            try {
                $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASSWORD);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':prenom', $prenom);
                $stmt->bindParam(':nom', $nom);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':telephone', $telephone);
                $stmt->bindParam(':adresse', $adresse);
                $stmt->bindParam(':code_postal', $code_postal);
                $stmt->bindParam(':ville', $ville);
                $stmt->bindParam(':cree_par', $nom);

                if ($stmt->execute()) {
                    $identifient = $conn->lastInsertId();
                    $isRedirectionNeeded = true; // Set the variable to indicate successful execution
                } else {
                    $output .= '<div class="alert alert-danger" role="alert">';
                    $output .= 'Failed to insert data.';
                    $output .= '</div>';
                }
            } catch (PDOException $e) {
                echo "ERROR: " . $e->getMessage();
            }
        } elseif (isset($_POST['is_adherent']) && $_POST['is_adherent'] === 'no') {
            // Handle form submission when 'is_adherent' is set to 'no'
           
                $prenomii = $_POST['prenom_personii'];
                $nomii = $_POST['nom_personii'];
                $emailii = $_POST['email_personii'];
                $telephoneii = $_POST['telephone_personii'];
                $adresseii = $_POST['adresse_personii'];
                $code_postalii = $_POST['code_postal_personii'];
                $villeii = $_POST['ville_personii'];
            

            $sql2 = "INSERT INTO `kipdev_auberge_adherents`(`prenom`, `nom`, `email`, `telephone`, `adresse`, `code_postal`, `ville`, `date_inscription`, `cree_par`) 
                    VALUES (:prenom, :nom, :email, :telephone, :adresse, :code_postal, :ville, CURRENT_TIMESTAMP, :cree_par)";

            try {
                $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASSWORD);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $stmt = $conn->prepare($sql2);

                $stmt->bindParam(':prenom', $prenomii);
                $stmt->bindParam(':nom', $nomii);
                $stmt->bindParam(':email', $emailii);
                $stmt->bindParam(':telephone', $telephoneii);
                $stmt->bindParam(':adresse', $adresseii);
                $stmt->bindParam(':code_postal', $code_postalii);
                $stmt->bindParam(':ville', $villeii);
                $stmt->bindParam(':cree_par', $nom);

                $stmt->execute();

                $adhesion_cost += 5;
                // Send email if insertion is successful
                if ($stmt->rowCount() > 0) {
                    $to = $emailii;
                    $subject = "Successful Insertion";
                    $message = "Hello, your data has been successfully inserted.";
                    $headers = "From:contact@aubergelesbainsdouches.fr";

                    if (mail($to, $subject, $message, $headers)) {
                        echo "Email sent successfully.";
                    } else {
                        echo "Failed to send email.";
                    }
                }
                $isRedirectionNeeded = true; // Set the variable to indicate successful execution
            } catch (PDOException $e) {
                echo "Database connection failed: " . $e->getMessage();
            }

            $conn = null; // Close the database connection

        } else {
            $output .= '<div class="alert alert-success" role="alert">';
            $output .= 'Toutes les informations requises n\'ont pas été fournies ' . ($i + 2);
            $output .= '</div>';
        }

        if ($isRedirectionNeeded) {
            $query_string = http_build_query(array('selected_beds' => $selected));

             $redirect_url = "/reservation_step2?id_adherent=$identifient&adhesion_cost=$adhesion_cost&checkin_date=$checkin_date&checkout_date=$checkout_date&nombre_nuit=$nombre_nuit&nombre_place=$nombre_place&email=$email&$query_string";

            wp_redirect($redirect_url);
            exit;
        }   
    }

    return $output;
}
add_shortcode('reservation_control', 'reservation_filter');


////////////////////////////////////////////////////////////////
///////////////////////step 2 reservation///////////////////////
function reservation_step2()
{
    // Get the reservation details from the GET parameters
    $checkin_date = $_GET['checkin_date'];
    $checkout_date = $_GET['checkout_date'];
    $id_adherent = $_GET['id_adherent'];
    $nombre_place = $_GET['nombre_place'];
    $nombre_nuit = $_GET['nombre_nuit'];
    $selected_beds = $_GET['selected_beds']; // Fix: Use 'selected_beds' instead of 'selected'
    $adhesion_cost = $_GET['adhesion_cost'];
    $email = $_GET['email'];

    // Calculate the reservation total
    $totalHT = $nombre_place * 22 * $nombre_nuit + $adhesion_cost;
    $tax_sejour = 0.65 * $nombre_nuit * $nombre_place;
    $total_a_payer = $totalHT + $tax_sejour;

    // Set the reservation status to "En cours..."
    $statut = "En cours...";

    // Get the user who made the reservation
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $faite_par = $current_user->user_login;
    } else {
        $faite_par = "Adhérent";
    }

    if (isset($_POST['btn_reserver'])) {
        // Retrieve the form data
        // ...

        try {
            // Connect to the database and insert the reservation
            $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASSWORD);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Insert the reservation into the "kipdev_auberge_reservations" table
            $stmt = $conn->prepare("INSERT INTO kipdev_auberge_reservations 
                (id_adherent, date_arrivee, date_depart, faite_par, statut) 
                VALUES (:id_adherent, :date_arrivee, :date_depart, :faite_par, :statut)");
            $stmt->bindParam(':id_adherent', $id_adherent);
            $stmt->bindParam(':date_arrivee', $checkin_date);
            $stmt->bindParam(':date_depart', $checkout_date);
            $stmt->bindParam(':faite_par', $faite_par);
            $stmt->bindParam(':statut', $statut);
            $stmt->execute();

            // Get the ID of the reservation that was just inserted
            $id_reservation = $conn->lastInsertId();

            // Insert the reservation details into the "kipdev_auberge_reservation_details" table
            foreach ($selected_beds as $bed) {
                $stmt = $conn->prepare("INSERT INTO kipdev_auberge_reservation_details 
                    (id_reservation, id_lit, nombre_place) VALUES (:id_reservation, :id_lit, :nombre_place)");
                $stmt->bindParam(':id_reservation', $id_reservation);
                $stmt->bindParam(':id_lit', $bed);
                $stmt->bindParam(':nombre_place', $nombre_place);

                // Execute the statement
                $stmt->execute();

                // Check if the insertion was successful
                if ($stmt->rowCount() < 1) {
                    // Handle the case where the insertion failed
                    throw new Exception("Failed to insert reservation details for bed: $bed");
                }
            }

            // Close the database connection
            $conn = null;

            // Redirect the users to respective locations
            if (is_user_logged_in() && (current_user_can('editor') || current_user_can('manage_options'))) {
                wp_redirect('/reservation-confirmation/?id_reservation=' . $id_reservation);
                exit;
            } else {
                header("Location: /reservation-confirmation-adherant?email=$email&id_reservation=$id_reservation&total_a_payer=$total_a_payer");
                 exit;
            }
        } catch (PDOException $e) {
            // Handle the database error
            echo "Error placing reservation: " . $e->getMessage();
        } catch (Exception $e) {
            // Handle the reservation details insertion error
            echo $e->getMessage();
        }
    }

    $output = '';
    $output .= '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">';
    $output .= '<div class="containers">';
    $output .= '<h4>Détail réservation</h4>';
    $output .= '<form action="" method="post" id="reservation-form">';
    $output .= '<div class="form-group row">
                    <label for="id_adherent" class="col-sm-2 col-form-label">Votre identifiant</label>
    <div class="col-sm-10">
      <input type="text" readonly class="form-control-plaintext" id="id_adherent" value="' . $id_adherent . '">
    </div>
  </div>';
    $output .= '<div class="container">
  <table class="table">
                    <tbody>
                        <tr>
                            <th scope="row">Prix unitaire par nuit</th>
                            <td>22 € </td>
                        </tr>
                        <tr>
                            <th scope="row">Nombre de lits à réserver</th>
                            <td>' . $nombre_place . ' </td>
                        </tr>
                        <tr>
                            <th scope="row">Nombre de nuits</th>
                            <td>' . $nombre_nuit . ' </td>
                        </tr>
                        <tr>
                        </tr>
                        <tr>
                            <th scope="row">Pour adhésion</th>
                            <td>' . $adhesion_cost . ' </td>
                        </tr>
                        <tr>
                            <th scope="row">Total HT</th>
                            <td>' . $totalHT . ' €</td>
                        </tr>
                        <tr>
                            <th scope="row">Taxe de séjour</th>
                            <td>' . $tax_sejour . ' €</td>
                        </tr>
                        <tr>
                            <th scope="row">Total à payer</th>
                            <td>' . ($totalHT + $tax_sejour) . ' €</td>
                        </tr>
    
                    </tbody>
                </table>';

    $output .= '<input type="hidden" name="nombre_place" value="' . $nombre_place . '" />
  <input type="hidden" name="checkin_date" value="' . $checkin_date . '" />
  <input type="hidden" name="checkout_date" value="' . $checkout_date . '" />
  <input type="hidden" name="faite_par" value="' . $faite_par . '" />
  <input type="hidden" name="total" value="' . $total_a_payer . '" />';
    $output .= '</div>';

    $output .= '<input type= "submit" name="btn_reserver" value="Réserver"  class ="btn-primary"/>';
    $output .= '</form>';
    $output .= '</div>';
    return $output;
}

add_shortcode('reservation_step2', 'reservation_step2');


//////////////////////////////////////
////////////////////////////////Show all reservations
function show_all_reservations()
{
    $content = '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">';
    // Connect to the database
    $content .= '<h4>Tous les réservations</h4>';
    $content .= '<div style="height: 800px; overflow-y: scroll;"><table class= "table">
    <thead>
        <tr>            
        <th>ID reserv</th>
        <th>Adhérant</th>
        <th>Arrival Date</th>
         <th>Departure Date</th>
         <th>Status</th>
        <th>Modifier</th>
        </tr>
    </thead>
    <tbody>';
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepare and execute the SQL query with a join operation
    $stmt = $conn->prepare("SELECT r.id_reservation, a.nom AS adherent_nom, r.date_arrivee, r.date_depart, r.statut FROM kipdev_auberge_reservations AS r
                            JOIN kipdev_auberge_adherents AS a ON r.id_adherent = a.id_adherent");
    $stmt->execute();

    // Fetch all the results as an array
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($reservations as $reservation) :
        $content .= '<tr>
        <td>' . $reservation['id_reservation'] . '</td>
        <td>' . $reservation['adherent_nom'] . '</td>       
        <td>' . $reservation['date_arrivee'] . '</td>
        <td>' . $reservation['date_depart'] . '</td>
        <td>' . $reservation['statut'] . '</td>
        <td><a href="?id_reservation=' . $reservation['id_reservation'] . '">Modif</a></td>
        </tr>';
    endforeach;

    $conn = null;

    $content .= '</tbody>
</table></div>';
    return $content;
}
add_shortcode('show_all_reservations', 'show_all_reservations');





////////////////////////////////////////////////////////////////
//////////////////Availability/////////////////////////////////
///////////////////////////////////////////////////////////////
function calendar_shortcode() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }

    // Perform the necessary database query to retrieve reservations and bed counts
    $query = "SELECT r.id_reservation, r.date_arrivee, r.date_depart, COUNT(rd.id_lit) AS bed_count
              FROM kipdev_auberge_reservations AS r
              JOIN kipdev_auberge_reservation_details AS rd ON r.id_reservation = rd.id_reservation
              GROUP BY r.id_reservation, r.date_arrivee, r.date_depart";
    $stmt = $pdo->query($query);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare the reservation data as an array of objects
    $calendarData = array();
    $bed_reserver =0;
    foreach ($reservations as $row) {
        $bed_reserver += $row['bed_count'];
        $reservation = array(
            'title' => 'Id =' .$row['id_reservation'] . '  Lits= '.$row['bed_count'],
            'start' => $row['date_arrivee'],
            'end' => $row['date_depart'],
            'bed_count' => $row['bed_count']
        );
        $calendarData[] = $reservation;
        
    }

    // Convert the reservation data to JSON
    $jsonData = json_encode($calendarData);

    // Generate the HTML code
    $content = '
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css" />
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.js"></script>
        <script>
            $(document).ready(function() {
                $("#calendar").fullCalendar({
                    events: ' . $jsonData .',
                    defaultView: "month",
                    header: {
                        left: "prev,next today",
                        center: "title",
                        right: "month,agendaWeek,agendaDay"
                    }
                });
            });
        </script>
        <div id="calendar"></div>
    ';

    // Return the HTML code
    return $content;
}

add_shortcode('availableLits', 'calendar_shortcode');






//payment

function payment()
{
    $content = ' ';
    $content .= '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">';

    require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

    $total_a_payer = $_GET['total_a_payer'];
    $id_reservation = $_GET['id_reservation'];

    $intent = \Stripe\PaymentIntent::create([
        'amount' => $total_a_payer * 100,
        'currency' => 'eur',
        'description' => 'Payment for the reavertion of dormitory. Reservation id: ' . $id_reservation . '',
    ]);

    $client_secret = $intent->client_secret;

    // Payment form
    $content .= '<div class="container">';
    $content .= '<form action="" method="POST" id="payment-form">';
    $content .= '<p>Payer un montant total de ' . $total_a_payer . '€ pour compléter votre réservation</p>';
    $content .= '<input type="hidden" name="price" value="' . $total_a_payer . '">';
    $content .= '<div class="row">';
    $content .= '<div class="col-md">';
    $content .= '<label for="stripeName" class="form-label"><i class="bi bi-person"></i> Votre NOM Prénom</label>';
    $content .= '<input type="text" name="stripeName" id="stripeName" class="form-control" placeholder="John Doe">';
    $content .= '</div>';
    $content .= '<div class="col-md">';
    $content .= '<label for="stripeEmail" class="form-label"><i class="bi bi-envelope"></i> Votre Email</label>';
    $content .= '<input type="email" name="stripeEmail" id="stripeEmail" class="form-control" placeholder="john.doe@example.com">';
    $content .= '</div>';
    $content .= '</div>';
    $content .= '<div class="row">';
    $content .= '<div class="col-md">';
    $content .= '<label for="card-element" class="form-label"><i class="bi bi-credit-card"></i> Numéro de carte</label>';
    $content .= '<div id="card-element" class="form-control"></div>';
    $content .= '</div>';
    $content .= '<div class="col-md">';
    $content .= '<label for="stripePhone" class="form-label"><i class="bi bi-telephone"></i> Téléphone</label>';
    $content .= '<input type="text" name="stripePhone" id="stripePhone" class="form-control" placeholder="+33 123 456 789">';
    $content .= '</div>';
    $content .= '</div>';
    $content .= '<button type="submit" class="btn btn-primary">Payer</button>';
    $content .= '</form>';


    // Stripe.js
    $content .= '<script src="https://js.stripe.com/v3/"></script>
     <script>
         var stripe = Stripe("' . STRIPE_PUBLIC_KEY . '");
         var elements = stripe.elements();
         
         var style = {
           base: {
             fontSize: "16px",
             color: "#32325d",
             fontFamily: "-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif",
             fontSmoothing: "antialiased",
             "::placeholder": {
               color: "rgba(0,0,0,0.4)"
             }
           },
              invalid: {
                fontFamily: "Arial, sans-serif",
                color: "#fa755a",
                iconColor: "#fa755a"
              }
            };
            
            var card = elements.create("card", { style: style });
            card.mount("#card-element");
            
            var form = document.getElementById("payment-form");
            
            form.addEventListener("submit", function(event) {
                event.preventDefault();
                
                stripe.confirmCardPayment("' . $client_secret . '", {
    payment_method: {
        card: card,
        billing_details: {
            name: "' . $_POST['stripeName'] . '",
            email: "' . $_POST['stripeEmail'] . '",
            phone: "' . $_POST['stripePhone'] . '"
        }
    }
}).then(function(result) {
    if (result.error) {
        alert("Payment failed. Error: " + result.error.message);
    } else {
        // Redirect to the success page
                        window.location.href = "/piment-accepter?id_resevation=' . $id_reservation . '/";
    }
});

            });
        </script>';
    $content .= '</div>';

    return $content;
}
add_shortcode('stripe_payment_form', 'payment');

////////////////////////////////////////////////////////////////
/////////////////Register payment//////////////////////////////
////////////////////////////////////////////////////////////////

function payment_sucessful()
{
    $id_reservation = $_GET['id_reservation'];
    $sql = "UPDATE `kipdev_auberge_reservations` SET `statut` = 'payer' WHERE `kipdev_auberge_reservations`.`id_reservation` = $id_reservation";

    try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASSWORD);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $conn->prepare($sql);
        $stmt->execute();

        $content = "Vous avez soumis avec succès le paiement pour la réservation du dortoir. Vous recevrez un email de confirmation de ce paiement.";
    } catch (PDOException $e) {
        $content = "Votre paiement a été accepté, mais une confirmation n'a pas été envoyée. Erreur : " . $e->getMessage();
    }

    return $content;
}
////////////////////////////////////////////////////////////////
///////////////modification ////////////////////////////////////
function modify_reservation($atts)
{
    //check if user is logged in and has permission to modify reservations
    if (!is_user_logged_in() || (!current_user_can('editor') && !current_user_can('administrator'))) {
        wp_redirect('/');
        exit;
    }

    $id_reservation = $_GET["id_reservation"];
    $content = 'Modification de reservation ';

    $content .= '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">';

    //check if form is submitted
    if (isset($_POST['submit'])) {

        //get details of selected reservation
        $options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        );

        try {
            // Connect to the database
            $dbh = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASSWORD, $options);
        } catch (PDOException $e) {
            echo 'Connection failed: ' . $e->getMessage();
        }

        //get variabled from form
        $date_arrivee = sanitize_text_field($_POST['date_arrivee']);
        $date_depart = sanitize_text_field($_POST['date_depart']);
        $statut = sanitize_text_field($_POST['statut']);

        // Update the reservation data
        $update_sql = 'UPDATE kipdev_auberge_reservations
                       SET date_arrivee = :date_arrivee,
                           date_depart = :date_depart,
                           statut = :statut
                       WHERE id_reservation = :id_reservation';

        $stmt = $dbh->prepare($update_sql);
        $stmt->bindParam(':date_arrivee', $date_arrivee);
        $stmt->bindParam(':date_depart', $date_depart);
        $stmt->bindParam(':statut', $statut);
        $stmt->bindParam(':id_reservation', $id_reservation, PDO::PARAM_INT);
        $stmt->execute();

        // display success message
        $content .= '<div class="alert alert-success" role="alert">Reservation updated successfully!</div>';
    }

    //check if "Supprimer" button is clicked
    if (isset($_POST['delete'])) {
        //delete the reservation and reservation details from the database
        $options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        );

        try {
            // Connect to the database
            $dbh = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASSWORD, $options);
        } catch (PDOException $e) {
            echo 'Connection failed: ' . $e->getMessage();
        }

        //delete the reservation details first
        $delete_reservation_details_sql = 'DELETE FROM kipdev_auberge_reservation_details WHERE id_reservation = :id_reservation';
        $stmt1 = $dbh->prepare($delete_reservation_details_sql);
        $stmt1->bindParam(':id_reservation', $id_reservation, PDO::PARAM_INT);
        $stmt1->execute();

        //delete the reservation
        $delete_reservation_sql = 'DELETE FROM kipdev_auberge_reservations WHERE id_reservation = :id_reservation';
        $stmt2 = $dbh->prepare($delete_reservation_sql);
        $stmt2->bindParam(':id_reservation', $id_reservation, PDO::PARAM_INT);
        $stmt2->execute();

        // display success message
        $content .= '<div class="alert alert-success" role="alert">Reservation deleted successfully!</div>';
    }
    //fetch the current reservation data from the database
    $options = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    );

    try {
        // Connect to the database
        $dbh = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASSWORD, $options);
    } catch (PDOException $e) {
        echo 'Connection failed: ' . $e->getMessage();
    }
    $id_reservation = $_GET["id_reservation"];

    $select_sql = 'SELECT date_arrivee, date_depart, statut FROM kipdev_auberge_reservations WHERE id_reservation = :id_reservation';
    $stmt = $dbh->prepare($select_sql);
    $stmt->bindParam(':id_reservation', $id_reservation, PDO::PARAM_INT);
    $stmt->execute();
    $reservation_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // display the reservation form with current data
    $content .= '<form method="post">';
    $content .= '<label for="date_arrivee" >Date d\'arrivée:</label>';
    $content .= '<input type="date" id="date_arrivee" name="date_arrivee" value ="' . $reservation_data["date_arrivee"] . '">';
    $content .= '<label for="date_depart">Date de départ:</label>';
    $content .= '<input type="date" id="date_depart" name="date_depart" value ="' . $reservation_data["date_depart"] . '">';
    $content .= '<label for="statut">Statut:</label>';
    $content .= '<input type="text" id="statut" name="statut" value ="' . $reservation_data["statut"] . '">';
    $content .= '<button type="submit" name="submit" class="btn btn-primary" >Update Reservation</button>';
    $content .= '<button type="submit" name="delete" class="btn btn-danger" >Supprimer</button>';
    $content .= '</form>';
    $dbh = null;

    return $content;
}

add_shortcode('modify_reservation', 'modify_reservation');
