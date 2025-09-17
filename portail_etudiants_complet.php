<?php
session_start();
// ---------------- CONFIG -----------------
$host = "localhost";
$user = "souldiajamaney";
$pass = "j@m@ney";
$db   = "portail_etudiants";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Erreur : " . $conn->connect_error); }
// ---------- CRÉATION TABLES SI NON EXISTE ----------
$conn->query("CREATE TABLE IF NOT EXISTS etudiants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    telephone VARCHAR(20) NOT NULL,
    niveau VARCHAR(50) NOT NULL,
    filiere VARCHAR(100) NOT NULL,
    matricule VARCHAR(50) UNIQUE NOT NULL,
    date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$conn->query("CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL
)");
// ---------- ADMIN PAR DÉFAUT ----------
$check = $conn->query("SELECT * FROM admin WHERE email='www.unipgdd@.com'");
if ($check->num_rows === 0) {
    $motdepasse = password_hash("unipgdd01", PASSWORD_DEFAULT);
    $conn->query("INSERT INTO admin (email, mot_de_passe) VALUES ('www.unipgdd@.com', '$motdepasse')");
}
// ---------- ROUTEUR ----------
$page = isset($_GET['page']) ? $_GET['page'] : 'inscription';
// ---------- DÉCONNEXION ----------
if ($page == "logout") {
    session_destroy();
    header("Location: ?page=login");
    exit();
}
// ---------- TRAITEMENT INSCRIPTION ----------
if ($page == "inscrire_etudiant" && isset($_POST['inscrire'])) {
    $nom     = $conn->real_escape_string($_POST['nom']);
    $prenom  = $conn->real_escape_string($_POST['prenom']);
    $email   = $conn->real_escape_string($_POST['email']);
    $tel     = $conn->real_escape_string($_POST['tel']);
    $niveau  = $conn->real_escape_string($_POST['niveau']);
    $filiere = $conn->real_escape_string($_POST['filiere']);
    
    $prefix = strtoupper(substr($niveau,0,2));
    $matricule = $prefix . "-" . date("Y") . "-" . rand(1000,9999);
    $conn->query("INSERT INTO etudiants (nom, prenom, email, telephone, niveau, filiere, matricule)
                  VALUES ('$nom','$prenom','$email','$tel','$niveau','$filiere','$matricule')");
    $message = "✅ Inscription réussie ! Votre matricule : $matricule";
}
// ---------- TRAITEMENT LOGIN ADMIN ----------
if ($page == "login_traitement") {
    $email = $_POST['email'];
    $mot_de_passe = $_POST['mot_de_passe'];
    $sql = "SELECT * FROM admin WHERE email=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        if (password_verify($mot_de_passe, $row['mot_de_passe'])) {
            $_SESSION['admin'] = $row['email'];
            header("Location: ?page=etudiants");
            exit();
        } else {
            $_SESSION['erreur'] = "Mot de passe incorrect.";
            header("Location: ?page=login");
            exit();
        }
    } else {
        $_SESSION['erreur'] = "Email admin non reconnu.";
        header("Location: ?page=login");
        exit();
    }
}
// ---------- AFFICHAGE PAGES ----------
if ($page == "login") { 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Connexion Admin</title>
<style>body{font-family:Arial;display:flex;justify-content:center;align-items:center;height:100vh;background:#f5f6fa}.login-box{background:white;padding:20px;border-radius:8px;box-shadow:0 0 10px rgba(0,0,0,0.2);width:300px}h2{text-align:center;color:#2c3e50}input,button{width:100%;padding:10px;margin:8px 0;border-radius:5px}button{background:#2c3e50;color:white;border:none;cursor:pointer}button:hover{background:#34495e}.error{color:red;text-align:center}</style>
</head>
<body>
<div class="login-box">
<h2>🔐 Connexion Admin</h2>
<form method="POST" action="?page=login_traitement">
<input type="email" name="email" placeholder="Email admin" required>
<input type="password" name="mot_de_passe" placeholder="Mot de passe" required>
<button type="submit">Se connecter</button>
</form>
<?php if(isset($_SESSION['erreur'])){echo "<p class='error'>".$_SESSION['erreur']."</p>";unset($_SESSION['erreur']);}?>
</div>
</body>
</html>
<?php exit();}
// ---------- PAGE ADMIN ÉTUDIANTS ----------
if ($page == "etudiants") {
    if(!isset($_SESSION['admin'])){header("Location:?page=login");exit();}
    $sql="SELECT * FROM etudiants ORDER BY date_inscription DESC";
    $result=$conn->query($sql);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Administration - Étudiants</title>
<style>body{font-family:Arial;margin:20px;background:#f5f6fa}table{border-collapse:collapse;width:100%;margin-top:20px}th,td{border:1px solid #ccc;padding:8px;text-align:center}th{background:#2c3e50;color:white}tr:nth-child(even){background:#f2f2f2}.logout{float:right;background:#c0392b;color:white;padding:8px 12px;text-decoration:none;border-radius:5px}.logout:hover{background:#e74c3c}</style>
</head>
<body>
<h2>📋 Liste des étudiants</h2>
<a class="logout" href="?page=logout">Se déconnecter</a>
<table>
<tr><th>ID</th><th>Matricule</th><th>Nom</th><th>Prénom</th><th>Email</th><th>Téléphone</th><th>Niveau</th><th>Filière</th><th>Date</th></tr>
<?php if($result->num_rows>0){while($row=$result->fetch_assoc()){echo "<tr><td>".$row['id']."</td><td><b>".$row['matricule']."</b></td><td>".$row['nom']."</td><td>".$row['prenom']."</td><td>".$row['email']."</td><td>".$row['telephone']."</td><td>".$row['niveau']."</td><td>".$row['filiere']."</td><td>".$row['date_inscription']."</td></tr>";}}else{echo "<tr><td colspan='9'>Aucun étudiant inscrit.</td></tr>";}?>
</table>
</body>
</html>
<?php exit();} ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Inscription Étudiant</title>
<style>body{font-family:Arial;display:flex;justify-content:center;align-items:center;height:100vh;background:#ecf0f1}.form-box{background:white;padding:20px;border-radius:8px;box-shadow:0 0 10px rgba(0,0,0,0.2);width:350px}h2{text-align:center;color:#2c3e50}input,select,button{width:100%;padding:10px;margin:8px 0;border-radius:5px}button{background:#2c3e50;color:white;border:none;cursor:pointer}button:hover{background:#34495e}.success{color:green;text-align:center}</style>
</head>
<body>
<div class="form-box">
<h2>📝 Inscription Étudiant</h2>
<?php if(isset($message)) echo "<p class='success'>$message</p>"; ?>
<form method="POST" action="?page=inscrire_etudiant">
<input type="text" name="nom" placeholder="Nom" required>
<input type="text" name="prenom" placeholder="Prénom" required>
<input type="email" name="email" placeholder="Email" required>
<input type="text" name="tel" placeholder="Téléphone" required>
<select name="niveau" required>
<option>Licence 1</option>
<option>Licence 2</option>
<option>Licence 3</option>
<option>Master 1</option>
<option>Master 2</option>
</select>
<input type="text" name="filiere" placeholder="Filière" required>
<button type="submit" name="inscrire">S'inscrire</button>
</form>
<p style="text-align:center;margin-top:10px;"><a href="?page=login">Connexion Admin</a></p>
</div>
</body>
</html>
