<?/**
 * Ajout,modification et suppresion de lit .
 *
 * Description.
 *
 * @since Version 3 digits
 */

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