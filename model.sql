CREATE TABLE kipdev_auberge_dortoirs (
  id_dortoir INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(255) NOT NULL,
  nombre_lits INT NOT NULL,
  lien_image VARCHAR(255),
  statut VARCHAR(30) NOT NULL,
  descriptions TEXT NULL,
  date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS kipdev_auberge_lits;
CREATE TABLE kipdev_auberge_lits (
  id_lit INT AUTO_INCREMENT PRIMARY KEY,
  id_dortoir INT NOT NULL,
  numero_lit INT NOT NULL,
  disponible BOOLEAN DEFAULT true,
  type_lit VARCHAR(30) NOT NULL,
  FOREIGN KEY (id_dortoir) REFERENCES kipdev_auberge_dortoirs(id_dortoir)
);

CREATE TABLE kipdev_auberge_adherents (
  id_adherent INT AUTO_INCREMENT PRIMARY KEY,
  prenom VARCHAR(255) NOT NULL,
  nom VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  telephone VARCHAR(20),
  adresse VARCHAR(255),
  code_postal VARCHAR(10),
  ville VARCHAR(100)
  date_inscription DATE DEFAULT CURRENT_TIMESTAMP,
  cree_part VARCHAR(60) NOT NULL
);

CREATE TABLE kipdev_auberge_reservations (
  id_reservation INT AUTO_INCREMENT PRIMARY KEY,
  id_adherent INT NOT NULL,
  date_arrivee DATE NOT NULL,
  date_depart DATE NOT NULL,
  date_reservation DATE DEFAULT CURRENT_TIMESTAMP,
  faite_par VARCHAR(45) DEFAULT NULL,
  statut VARCHAR(45) DEFAULT NULL,
  FOREIGN KEY (id_adherent) REFERENCES kipdev_auberge_adherents(id_adherent)
);


CREATE TABLE kipdev_auberge_reservation_details (
  id_reservation_detail INT AUTO_INCREMENT PRIMARY KEY,
  id_reservation INT NOT NULL,
  id_lit INT NOT NULL,
  nombre_place INT(11) NOT NULL,
  FOREIGN KEY (id_reservation) REFERENCES kipdev_auberge_reservations(id_reservation),
  FOREIGN KEY (id_lit) REFERENCES kipdev_auberge_lits(id_lit)
);


