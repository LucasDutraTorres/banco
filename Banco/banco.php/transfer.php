<?php
/**
 * SCRIPT: transfer.php
 * Realiza transferência entre contas e persiste no banco de dados.
 */
header("Content-Type: application/json; charset=UTF-8");
ini_set('display_errors', 0);

try {
    include_once 'conexao.php';
    include_once 'usuario.php';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $fromEmail = isset($_POST['fromEmail']) ? trim($_POST['fromEmail']) : null;
        $toEmail = isset($_POST['toEmail']) ? trim($_POST['toEmail']) : null;
        $valor = isset($_POST['valor']) ? floatval($_POST['valor']) : null;

        // Validações básicas
        if (!$fromEmail || !$toEmail || !$valor) {
            echo json_encode(["status" => "error", "message" => "Dados incompletos."]);
            exit();
        }

        if ($fromEmail === $toEmail) {
            echo json_encode(["status" => "error", "message" => "Não é possível transferir para a mesma conta."]);
            exit();
        }

        if ($valor <= 0) {
            echo json_encode(["status" => "error", "message" => "O valor deve ser maior que zero."]);
            exit();
        }

        $usuario = new Usuario($conn);

        // 1. Verifica se ambos os usuários existem
        $remetente = $usuario->buscarPorEmail($fromEmail);
        $destinatario = $usuario->buscarPorEmail($toEmail);

        if ($remetente['status'] !== 'success') {
            echo json_encode(["status" => "error", "message" => "Remetente não encontrado."]);
            exit();
        }

        if ($destinatario['status'] !== 'success') {
            echo json_encode(["status" => "error", "message" => "Destinatário não encontrado."]);
            exit();
        }

        // 2. Verifica o saldo do remetente
        $dadosRemetente = $usuario->buscarDadosCompletos($fromEmail);
        $saldoRemetente = $dadosRemetente['saldo'];

        if ($saldoRemetente < $valor) {
            echo json_encode(["status" => "error", "message" => "Saldo insuficiente para realizar a transferência."]);
            exit();
        }

        // 3. Realiza a transferência
        $resultado = $usuario->transferir($fromEmail, $toEmail, $valor);
        echo json_encode($resultado);

    } else {
        echo json_encode(["status" => "error", "message" => "Método não permitido."]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Erro no servidor: " . $e->getMessage()]);
}
exit();
?>