<?php
header("Content-Type: application/json; charset=UTF-8");
include_once 'conexao.php';
include_once 'Usuario.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = new Usuario($conn);
    $email = $_POST['email'];

    $resultado = $usuario->buscarDadosCompletos($email);
    echo json_encode($resultado);
}
?>
