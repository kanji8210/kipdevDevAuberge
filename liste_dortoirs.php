<?/**
 * show the list of all dormitories.
 *
 * Description.
 *
 * @since Version 3 digits
 */
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
