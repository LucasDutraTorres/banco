<?php
/**
 * SCRIPT: deposit.php
 * Realiza depósito em uma conta e persiste no banco de dados.
 */
header("Content-Type: application/json; charset=UTF-8");
ini_set('display_errors', 0);

try {
    include_once 'conexao.php';
    include_once 'usuario.php';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = isset($_POST['email']) ? trim($_POST['email']) : null;
        $valor = isset($_POST['valor']) ? floatval($_POST['valor']) : null;

        // Validações básicas
        if (!$email || !$valor) {
            echo json_encode(["status" => "error", "message" => "Dados incompletos."]);
            exit();
        }

        if ($valor <= 0) {
            echo json_encode(["status" => "error", "message" => "O valor deve ser maior que zero."]);
            exit();
        }

        $usuario = new Usuario($conn);

        // 1. Verifica se o usuário existe
        $usuarioExiste = $usuario->buscarPorEmail($email);

        if ($usuarioExiste['status'] !== 'success') {
            echo json_encode(["status" => "error", "message" => "Usuário não encontrado."]);
            exit();
        }

        // 2. Realiza o depósito
        $resultado = $usuario->depositar($email, $valor);
        echo json_encode($resultado);

    } else {
        echo json_encode(["status" => "error", "message" => "Método não permitido."]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Erro no servidor: " . $e->getMessage()]);
}
exit();
?>