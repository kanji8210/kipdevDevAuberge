<?php

/**
 * Plugin Name:       kipdev_gestion_dortoirs
 * Description:       manage a small guest house.
 * Version:           1.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Kipdev wp solutions (Dennis K)
 * Author URI:        https://denniskip.com/
 * License:           GPL v2 or later
 **/

function kipdev_auberge()
{
    define('MY_PLUGIN_PATH', plugin_dir_path(__FILE__));
    include MY_PLUGIN_PATH . 'backoffice.php';
}
add_shortcode('back_office', 'kipdev_auberge');

////////////////////////////////////////////////////////////////
//gestion dortoir
////////////////////////////////////////////////////////////////

function kipdev_gestion_dortoir()
{

    //intitialization of variables...
    if (isset($_POST)) {
        $nom = (isset($_POST['nom'])) ? sanitize_text_field($_POST['nom']) : '';
        $no_of_beds = (isset($_POST['nombre_lits'])) ? sanitize_text_field($_POST['nombre_lits']) : '';
        $description = (isset($_POST['description'])) ? sanitize_text_field($_POST['description']) : '';
        $statut = (isset($_POST['status'])) ? sanitize_text_field($_POST['status']) : '';
    }
    //var_dump($_POST);
    //treating image
    if (isset($_FILES)) {

        $feature_image = $_FILES['feature_image'];
        $urlFeartureimage = "";
        $name_feature_image = $feature_image['name'];

        if ($feature_image['error'] === UPLOAD_ERR_OK) {
            $tempname = $feature_image['tmp_name'];
            $destination = './wp-content/uploads/2022/12/' . $name_feature_image;
            $size = $feature_image['size'];
            //we get extention of the name
            $extension = pathinfo($name_feature_image, PATHINFO_EXTENSION);

            //controle the size
            if ($size <= 1000000) {
                if (in_array($extension, ['jpg', 'png', 'jpeg'])) {
                    if (move_uploaded_file($tempname, $destination)) {
                        $urlFeartureimage = 'https://aubergelesbainsdouches.fr/wp-content/uploads/2022/12/' . $name_feature_image;
                        //echo $urlFeartureimage;

                    } else {
                        echo "unable to move uploaded file to $destination";
                    }
                } else {
                    echo "Extension is not allowed";
                }
            } else {
                echo "file too large";
            }
        } else {
            echo "there was an error uploading feature image to the database";
        }
    }
    if (isset($_POST) && isset($_POST['submit']) && (!isset($_GET['descriptions']))) {
        $insertsql = "INSERT INTO `kipdev_auberge_dortoirs`(`id_dortoir`, `nom`, `descriptions`, `nombre_lits`, `lien_image`, `statut`)
        VALUES (:id_dortoir,
                :nom,
                :descriptions,
                :nombre_lits,
                :lien_image,
                :statut)";
        try {
            $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASSWORD);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $stmp = $conn->prepare($insertsql);
            $stmp->bindValue('id_dortoir', $id_dortoir, PDO::PARAM_INT);
            $stmp->bindValue('nom', $nom, PDO::PARAM_STR);
            $stmp->bindValue('nombre_lits', $no_of_beds, PDO::PARAM_INT);
            $stmp->bindValue('descriptions', $description, PDO::PARAM_STR);
            $stmp->bindValue('lien_image', $urlFeartureimage, PDO::PARAM_STR);
            $stmp->bindValue('statut', $statut, PDO::PARAM_STR);
            $stmp->execute();

            echo "dortoir has been created.";
            $id_dortoir = $conn->lastInsertId();

            header("Location:/gestions-des-dortoirs/?id_dortoir=$id_dortoir");
        } catch (PDOException $e) {
            echo "error connecting to database:" . $e;
        }
        $conn = null;
    }
    //si dortoir exit
    elseif (isset($_GET['id_dortoir'])) {

        $btn_modify = '<input type="submit" name="btn_modify" class="btn btn-primary" id="submit_btn" value="Modifier">';
        $btn_delete = '<input type="submit" name="delete" class="btn-danger" value="Supprimer" <i class="bi bi-trash-fill"></i>';
        $titre_form = 'Modification de dortoir';

        $id_dortoir = ($_GET['id_dortoir']);
        //echo "id_dortoir= " . $id_dortoir;

        if (isset($_POST['btn_modify'])) {

            $sql = "UPDATE `kipdev_auberge_dortoirs`
                        SET
                        `nom`              = :nom,
                        `descriptions`          = :descriptions,
                        `nombre_lits`              = :nombre_lits,
                         `lien_image`              =:lien_image,
                        `statut`                   =:statut
                         WHERE id_dortoir         =:id_dortoir";

            try {
                $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASSWORD);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $smt = $conn->prepare($sql);

                $smt->bindValue(':id_dortoir', $id_dortoir, PDO::PARAM_INT);
                $smt->bindValue(':nom', $nom, PDO::PARAM_STR);
                $smt->bindValue(':nombre_lits', $no_of_beds, PDO::PARAM_INT);
                $smt->bindValue(':descriptions', $description, PDO::PARAM_STR);
                $smt->bindValue(':lien_image', $urlFeartureimage, PDO::PARAM_STR);
                $smt->bindValue(':statut', $statut, PDO::PARAM_STR);

                $smt->execute();

                header("Location: /gestions-des-dortoirs/?id_dortoir=$id_dortoir");
            } catch (PDOException $e) {
                echo "An error occurred while updating ab_dortoir " . $e;
            }
            $conn = null;
        }
        if (isset($_POST['delete'])) {
            $sql = "DELETE FROM kipdev_auberge_dortoirs
            where id_dortoir = :id_dortoir";

            try {
                $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASSWORD);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $smt = $conn->prepare($sql);
                $smt->bindValue(':id_dortoir', $id_dortoir, PDO::PARAM_INT);
                $smt->execute();

                header("Location: /gestions-des-dortoirs");
                return "dortoir supprimer";
            } catch (PDOException $e) {
                echo "Error while deleting dortoir" . $e;
            }
            $conn = null;
        }
        //select dortoir
        $select_stmt = "SELECT * FROM kipdev_auberge_dortoirs WHERE id_dortoir = :id_dortoir";
        try {
            $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASSWORD);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $stmt = $conn->prepare($select_stmt);
            $stmt->bindValue(':id_dortoir', $id_dortoir);
            $stmt->execute([':id_dortoir' => $id_dortoir]);

            $dortoir = $stmt->fetch(PDO::FETCH_ASSOC);

            $description = $dortoir['descriptions'];
            $nom = $dortoir['nom'];
            $nombre_lits = $dortoir['nombre_lits'];
            $statut = $dortoir['statut'];
            $urlFeartureimage = $dortoir['lien_image'];
        } catch (PDOException $e) {
            echo "Error while seleting dortoir" . $e;
        }
        $conn = null;
        $form_action = '/gestions-des-dortoirs/?id_dortoir=' . $id_dortoir . '';

        $display_image = '<div class="container">
        <img class="img-fluid" src="' . $urlFeartureimage . '"> 
        </div>';
    } else {
        $btn_submit = '<input type="submit" name= "submit" value="Soumetre" class="btn btn-primary" >';
        $form_action = '/gestions-des-dortoirs/';
        $titre_form = "Création dortoir";
    }

    //html
    $content = '
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
    <!--creation dortoirs-->
    <h3 class=" text-info text-center"> ' . $titre_form . '</h3>
    <form method="post" action="' . $form_action . '" accept-charset="utf-8" enctype="multipart/form-data">
    <div class="container">
        <div class="row">
            <div class="col">
                <label for="nom">Nom doctoirs</label>
                <input type="text" name="nom" class="form-control" value="' . $nom . '">
            </div>
            <div class="col">
                <label for="nombre_lits">Nombre des lits</label>
                <input type="number" name="nombre_lits" class="form-control" value="' . $nombre_lits . '">
            </div>
            <div class="col">
                <label for="status">Disponibilité"</label>
                <select name="status" class="form-select">
                <option selected value ="' . $statut . '">' . $statut . '</option>
                <option value="Disponible">Disponible</option>
                <option value="Indisponible">Indisponible</option>
                </select>
            </div>
        </div>
        <label for="description">Description</label>
        <textarea id="description" name="description" class="form-control" rows="3">' . $description . '</textarea>
        <br>
        <div class="row">
            <div class="container"> 
            ' . $display_image . '
                <label for="feature_image"> Image mis en avant </label>
                <input type="file" name="feature_image" class="form-control">
                <input type="hidden" name="feature_image" id="feature_image" value="' . $urlFeartureimage . '";
                <br>
                </div>
            <br>
        </div>
        <br>
        <div class="row">
        <br>
            <div class="col">
                ' . $btn_submit . $btn_modify . '
            </div>
            <div class="col">
                ' . $btn_delete . '
            </div>
            <div class="col">
            <a href="/gestion-lits/" class="class="btn btn-info"zzzzzzzzzzz
            
            </div>
        </div>
    </div>
    </form>';
    return $content;
}

add_shortcode('kipdev_gestion_dortoir', 'kipdev_gestion_dortoir');

function list_dortoir()
{
    $selectQL = 'SELECT * FROM kipdev_auberge_dortoirs ORDER BY id_dortoir DESC';
    $content = '';
    try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASSWORD);
        $resultat = $conn->query($selectQL);
        //si il y a plus que Zéro ligne dans resultat
        if ($resultat) {

            $content .= '<link rel="stylesheet" type="text/css" href="kipdev_style.css"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">

<div>
<h4>Les dortoirs</h4>
<ul>';
            while ($row = $resultat->fetch()) {
                $content .= '
            <li class="list-group-item" ><h4>' . $row['nom'] . '</h4> Disponibilité: ' . $row['statut'] . ' 
            <a href="/gestions-des-dortoirs/?id_dortoir=' . $row['id_dortoir'] . '"><i class="bi bi-pencil-fill"></i></a>
            </li>';
            }
            $content .= '
            </ul>
            </div>';
            $content .= '<a href="/gestions-des-dortoirs"><i class="bi bi-plus-circle-fill">Ajouter nouveu dortoir</i></a>';
        }
    } catch (PDOException $e) {
        return $e;
    }
    return $content;
}
add_shortcode('liste-dortoir', 'list_dortoir');

//gestion des lits

function back_office()
{
    //double controlle access
    if (!is_user_logged_in()) {
        wp_redirect("https://aubergelesbainsdouches.fr");
        exit;
    }

    $content = '
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
        <link rel="stylesheet" type="text/css" href="kipdev_style.css"/>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">';
    $content .= '<div class="container">';
    $content .= '<h3>Hemins Courts</h3>';
    $content .= '<div class="list-group">
    <a href="/gestions-des-reservation/" class="list-group-item list-group-item-action">Gestion des réservations</a>
    <a href="/reservation/" class="list-group-item list-group-item-action">Réserver pour un adhérent</a>
    <a href="/gestions-des-adherents/" class="list-group-item list-group-item-action">Gestion des adhérents</a>
    <a href="/gestions-des-dortoirs/" class="list-group-item list-group-item-action">Gestion des dortoirs</a>
    <a href="/gestion-lits/" class="list-group-item list-group-item-action">Gestion des lits</a>
    </div>';
    $content .= '</div>';
    return $content;
}
add_shortcode('back_office', 'back_office');

////////////////////////////////////////////////
/////////////////Gestion des lits//////////////
//////////////////////////////////////////////
function ajoutLit($id_lit)
{
    $numero_lit = $_POST['numero_lit'] ?? null;
    $id_dortoir = $_POST['id_dortoir'] ?? null;
    $disponible = $_POST['disponible'] ?? null;
    $type_lit = $_POST['type_lit'] ?? null;

    if (isset($_POST['btn_ajouter']) && !isset($_GET['id_lit'])) {
        $sqlInsert = "INSERT INTO kipdev_auberge_lits (numero_lit, id_dortoir, disponible, type_lit) 
                  VALUES (:numero_lit, :id_dortoir, :disponible, :type_lit)";
        try {
            $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASSWORD);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $stmt = $conn->prepare($sqlInsert);
            $stmt->bindParam(':numero_lit', $numero_lit);
            $stmt->bindParam(':id_dortoir', $id_dortoir);
            $stmt->bindParam(':disponible', $disponible);
            $stmt->bindParam(':type_lit', $type_lit);
            $stmt->execute();
            $id_lit = $conn->lastInsertId();
            header("Location: /gestion-lits?id_lit=$id_lit");
            exit;
        } catch (PDOException $e) {
            echo "Something went wrong with insertion of bed" . $e;
            exit;
        }
    } elseif (isset($_GET['id_lit'])) {
        $id_lit = $_GET['id_lit'];
        $action = "/gestion-lits?id_lit={$id_lit}";
        $btn_submit = '<input type="submit" class="btn btn-warning" name="btn_modifier" value="Modifier">';
        $btn_supprimer = '<input type="submit" class="btn btn-danger" name="btn_supprimer" value="Supprimer">';
        $titre_Formulaire = "<h4>Modifier information sur le lit</h4>";

        if (isset($_POST['btn_modifier'])) {
            $sqlmodif = "UPDATE `kipdev_auberge_lits` 
            SET `id_dortoir`= :id_dortoir,
            `numero_lit`=:numero_lit,
            `disponible`=:disponible,
            `type_lit`=:type_lit
             WHERE `id_lit` = :id_lit";
            try {
                $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASSWORD);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $stmt = $conn->prepare($sqlmodif);
                $stmt->bindParam(':id_lit', $id_lit);
                $stmt->bindParam(':numero_lit', $numero_lit);
                $stmt->bindParam(':id_dortoir', $id_dortoir);
                $stmt->bindParam(':disponible', $disponible);
                $stmt->bindParam(':type_lit', $type_lit);
                $stmt->execute();
                header("Location: /gestion-lits?id_lit=$id_lit");
                exit;
            } catch (PDOException $e) {
                echo "Something went wrong with updating bed" . $e;
                exit;
            }
        } elseif (isset($_POST['btn_supprimer'])) {
            try {
                $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASSWORD);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $stmt = $conn->prepare("DELETE FROM kipdev_auberge_lits WHERE id_lit = :id_lit");
                $stmt->bindParam(':id_lit', $id_lit);
                $stmt->execute();
                header("Location: /gestion-lits");
                exit;
            } catch (PDOException $e) {
                echo "Something went wrong with deleting bed" . $e;
            }
        }
    } else {
        $titre_Formulaire = "<h4>Ajouter un lit</h4>";
        $action = "/gestion-lits/";
        $btn_submit = '<input type="submit" class = "btn btn-primary" name ="btn_ajouter" value ="Ajouter">';
    }

    $content = '
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
        <link rel="stylesheet" type="text/css" href="kipdev_style.css"/>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
        ' . $titre_Formulaire;

    $content .= '<form action="' . $action . '" method="post">
        <div class="form-group">
        <label for="numero_lit">Numéro de lit:</label>
        <input type="number" class="form-control" id="numero_lit" name="numero_lit" required value="' . $numero_lit . '">
        </div>';
    $content .= '<div class="form-group">
        <label for="id_dortoir">Dortoir:</label>
        <select class="form-control" id="id_dortoir" name="id_dortoir">';
    // Create a PDO connection to the database
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Select all the dormitories from the database
    $stmt = $conn->prepare("SELECT id_dortoir, nom FROM kipdev_auberge_dortoirs");
    $stmt->execute();
    $dorms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Create an option element for each dormitory
    foreach ($dorms as $dorm) {
        $content .= "<option value=\"" . $dorm['id_dortoir'] . "\">" . $dorm['nom'] . "</option>";
    }
    $content .= '</select>
         </div>';

    $content .= '
        <div class="form-group">
        <label for="disponible">Disponibilité:</label>
        <select class="form-control" id="disponible" name="disponible" required>
        <option value="1">Disponible</option>
        <option value="0">Indisponible</option>
        </select>
        </div>

        <div class="form-group">
        <label for="type_lit">Type de lit:</label>
        <select class="form-control" id="type_lit" name="type_lit" >
        <option value="Simple">Simple</option>
        <option value="Double">Double</option>
        </select>
        </div>

        <br>
        <div class="row">
        <div class="col-md">
        ' . $btn_submit . '
        </div>
        <div class="col-md">
        ' . $btn_supprimer . '
        </div>
        </div>

        </form>';
    $content .= '</div>';
    $content .= '</div>';

    return $content;
}
add_shortcode('ajoutLit', 'ajoutLit');
////////////////////////////////////////////////////////////////
function list_lits()
{
    $content = '
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="kipdev_style.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">';
    $sql = "SELECT kipdev_auberge_lits.id_lit, kipdev_auberge_lits.numero_lit, kipdev_auberge_dortoirs.nom, kipdev_auberge_lits.type_lit,  kipdev_auberge_lits.disponible 
            FROM kipdev_auberge_lits 
            INNER JOIN kipdev_auberge_dortoirs 
            ON kipdev_auberge_lits.id_dortoir = kipdev_auberge_dortoirs.id_dortoir ";
    try {
        // Connect to the database using PDO
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASSWORD);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Execute the query and fetch the results
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);


        // Display the results in a list
        if ($results) {
            $content .= "<h3>Tous les lits</h3>";
            $content .= '<div class ="list-group">';
            foreach ($results as $row) {
                $id_lit = $row['id_lit'];
                $numero_lit = $row['numero_lit'];
                $nom_dortoirs = $row['nom'];
                $statut = $row['disponible'];
                $type = $row['type_lit'];
                if ($statut == 1) {
                    $status = "Disponible";
                } else {
                    $status = "Indisponible";
                }
                $content .= '<a href="/gestion-lits/?id_lit=' . $id_lit . '" class = "list-group-item list-group-item-action"><em>N° de lit: </em>' . $numero_lit . ' <em>dortoir: </em>' . $nom_dortoirs . ' <em>statut: </em>' . $status . ' <em>type: </em>' . $type . '</a>';
            }
            $content .= "</div>";
            echo "id_lit =" . $id_lit;
        }
    } catch (PDOException $e) {
        // Handle any errors that may occur
        echo "Error: " . $e->getMessage();
        $conn = null;
    }

    return $content;
}
add_shortcode('list_lites', 'list_lits');
//Back office
