<?php
/**
 * SCRIPT: loan.php
 * Realiza empréstimo, atualiza saldo e registra no histórico.
 */
header("Content-Type: application/json; charset=UTF-8");
ini_set('display_errors', 0);

try {
    include_once 'conexao.php';
    include_once 'usuario.php';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = isset($_POST['email']) ? trim($_POST['email']) : null;
        $valor = isset($_POST['value']) ? floatval($_POST['value']) : null;
        $parcelas = isset($_POST['installments']) ? intval($_POST['installments']) : null;

        // Validações básicas
        if (!$email || $valor === null || !$parcelas) {
            echo json_encode(["status" => "error", "message" => "Dados incompletos."]);
            exit();
        }

        if ($valor <= 0) {
            echo json_encode(["status" => "error", "message" => "O valor deve ser maior que zero."]);
            exit();
        }

        if ($parcelas < 1 || $parcelas > 60) {
            echo json_encode(["status" => "error", "message" => "Número de parcelas inválido (1-60)."]);
            exit();
        }

        $usuario = new Usuario($conn);

        // 1. Verifica se o usuário existe
        $usuarioExiste = $usuario->buscarPorEmail($email);

        if ($usuarioExiste['status'] !== 'success') {
            echo json_encode(["status" => "error", "message" => "Usuário não encontrado."]);
            exit();
        }

        // 2. Realiza o empréstimo
        $resultado = $usuario->emprestar($email, $valor, $parcelas);
        echo json_encode($resultado);

    } else {
        echo json_encode(["status" => "error", "message" => "Método não permitido."]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Erro no servidor: " . $e->getMessage()]);
}
exit();
?>