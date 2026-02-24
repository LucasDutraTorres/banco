<?php
/**
 * CLASSE: Usuario
 * Gerencia as operações de usuário no Banco de Dados MySQL.
 */
class Usuario {
    private $conn;
    private $tabela = "usuario";

    public $id;
    public $nome;
    public $email;
    public $saldo;

    public function __construct($db) {
        $this->conn = $db;
    }

    private function gerarIdAleatorio() {
        $novoId = "";
        for ($i = 0; $i < 11; $i++) {
            $novoId .= rand(0, 9);
        }
        return $novoId;
    }

    private function gerarIdAleatorio8Digitos() {
        $novoId = "";
        for ($i = 0; $i < 8; $i++) {
            $novoId .= rand(0, 9);
        }
        return $novoId;
    }

    /**
     * Método para criar uma nova conta (Cadastro)
     */
    public function criar() {
        $this->email = trim($this->email);

        $queryBusca = "SELECT COUNT(*) as total FROM " . $this->tabela . " WHERE email = ?";
        $stmtBusca = $this->conn->prepare($queryBusca);
        $stmtBusca->bind_param("s", $this->email);
        $stmtBusca->execute();
        $resultado = $stmtBusca->get_result()->fetch_assoc();

        if ($resultado['total'] > 0) {
            return ["status" => "error", "message" => "Este e-mail já está cadastrado. Tente outro."];
        }

        $this->id = $this->gerarIdAleatorio();
        $this->saldo = 0.00;

        $queryInsert = "INSERT INTO " . $this->tabela . " (id, nome, email, saldo) VALUES (?, ?, ?, ?)";
        $stmtInsert = $this->conn->prepare($queryInsert);
        $stmtInsert->bind_param("sssd", $this->id, $this->nome, $this->email, $this->saldo);

        if ($stmtInsert->execute()) {
            return ["status" => "success", "message" => "Conta criada com sucesso!", "id" => $this->id];
        }
        
        return ["status" => "error", "message" => "Erro técnico ao salvar no banco."];
    }

    /**
     * Método para buscar um usuário pelo e-mail (Login)
     */
    public function buscarPorEmail($email) {
        $email = trim($email);
        $query = "SELECT id, nome, email, saldo FROM " . $this->tabela . " WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($user = $resultado->fetch_assoc()) {
            return [
                "status" => "success", 
                "id" => $user['id'],
                "nome" => $user['nome'], 
                "email" => $user['email'],
                "saldo" => (float)$user['saldo']
            ];
        }
        return ["status" => "error", "message" => "Usuário não encontrado."];
    }

    /**
     * Método para buscar dados completos (Saldo e Nome para a Index)
     */
    public function buscarDadosCompletos($email) {
        $email = trim($email);
        $query = "SELECT nome, saldo FROM " . $this->tabela . " WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($user = $resultado->fetch_assoc()) {
            return [
                "status" => "success", 
                "nome" => $user['nome'], 
                "saldo" => (float)$user['saldo']
            ];
        }
        return ["status" => "error", "message" => "Usuário não encontrado."];
    }

    /**
     * Método para depositar dinheiro em uma conta
     */
    public function depositar($email, $valor) {
        $email = trim($email);
        try {
            $query = "UPDATE " . $this->tabela . " SET saldo = saldo + ? WHERE email = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ds", $valor, $email);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                return [
                    "status" => "success",
                    "message" => "Depósito realizado com sucesso!",
                    "valor" => $valor,
                    "email" => $email
                ];
            } else {
                return [
                    "status" => "error",
                    "message" => "Usuário não encontrado."
                ];
            }
        } catch (Exception $e) {
            return [
                "status" => "error",
                "message" => "Erro ao realizar depósito: " . $e->getMessage()
            ];
        }
    }

    /**
     * Método para transferir dinheiro entre contas
     */
    public function transferir($fromEmail, $toEmail, $valor) {
        $fromEmail = trim($fromEmail);
        $toEmail = trim($toEmail);

        $this->conn->begin_transaction();

        try {
            // 1. Subtrai do remetente
            $querySubtrai = "UPDATE " . $this->tabela . " SET saldo = saldo - ? WHERE email = ?";
            $stmtSubtrai = $this->conn->prepare($querySubtrai);
            $stmtSubtrai->bind_param("ds", $valor, $fromEmail);
            $stmtSubtrai->execute();

            if ($stmtSubtrai->affected_rows === 0) {
                throw new Exception("Remetente não encontrado.");
            }

            // 2. Soma ao destinatário
            $querySoma = "UPDATE " . $this->tabela . " SET saldo = saldo + ? WHERE email = ?";
            $stmtSoma = $this->conn->prepare($querySoma);
            $stmtSoma->bind_param("ds", $valor, $toEmail);
            $stmtSoma->execute();

            if ($stmtSoma->affected_rows === 0) {
                throw new Exception("Destinatário não encontrado.");
            }

            // 3. Confirma a transação
            $this->conn->commit();

            return [
                "status" => "success",
                "message" => "Transferência realizada com sucesso!",
                "valor" => $valor,
                "remetente" => $fromEmail,
                "destinatario" => $toEmail
            ];

        } catch (Exception $e) {
            // Se algo deu errado, volta atrás
            $this->conn->rollback();
            return [
                "status" => "error",
                "message" => $e->getMessage()
            ];
        }
    }

    /**
     * Método para realizar empréstimo
     */
    public function emprestar($email, $valor, $parcelas) {
        $email = trim($email);
        $taxaJuros = 5; // 5%
        $id = $this->gerarIdAleatorio8Digitos(); // ID aleatório de 8 dígitos
        $data = date('Y-m-d H:i:s');

        try {
            // 1. Busca o saldo atual do usuário
            $queryBuscaSaldo = "SELECT saldo FROM " . $this->tabela . " WHERE email = ?";
            $stmtBuscaSaldo = $this->conn->prepare($queryBuscaSaldo);
            
            if (!$stmtBuscaSaldo) {
                throw new Exception("Erro ao preparar query: " . $this->conn->error);
            }
            
            $stmtBuscaSaldo->bind_param("s", $email);
            $stmtBuscaSaldo->execute();
            $resultadoSaldo = $stmtBuscaSaldo->get_result();
            $usuarioData = $resultadoSaldo->fetch_assoc();

            if (!$usuarioData) {
                throw new Exception("Usuário não encontrado.");
            }

            $saldoAtual = floatval($usuarioData['saldo']);
            
            // 2. Calcula o novo saldo (SOMA o valor do empréstimo ao saldo existente)
            $novoSaldo = $saldoAtual + floatval($valor);
            
            // 3. Atualiza o saldo na tabela usuario
            $queryAtualizaSaldo = "UPDATE " . $this->tabela . " SET saldo = ? WHERE email = ?";
            $stmtAtualizaSaldo = $this->conn->prepare($queryAtualizaSaldo);
            
            if (!$stmtAtualizaSaldo) {
                throw new Exception("Erro ao preparar update: " . $this->conn->error);
            }
            
            $stmtAtualizaSaldo->bind_param("ds", $novoSaldo, $email);
            
            if (!$stmtAtualizaSaldo->execute()) {
                throw new Exception("Erro ao executar update: " . $stmtAtualizaSaldo->error);
            }

            if ($stmtAtualizaSaldo->affected_rows === 0) {
                throw new Exception("Nenhuma linha foi atualizada.");
            }

            // 4. Registra no histórico
            $queryHistorico = "INSERT INTO historico (tipo, valor, data, destino, id, email_usuario, taxa_juros, num_parcelas) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtHistorico = $this->conn->prepare($queryHistorico);
            
            if (!$stmtHistorico) {
                throw new Exception("Erro ao preparar insert: " . $this->conn->error);
            }
            
            $tipo = "emprestimo";
            $destino = "conta";
            
            $stmtHistorico->bind_param(
                "sdssissi",
                $tipo,
                $valor,
                $data,
                $destino,
                $id,
                $email,
                $taxaJuros,
                $parcelas
            );
            
            if (!$stmtHistorico->execute()) {
                throw new Exception("Erro ao registrar no histórico: " . $stmtHistorico->error);
            }

            if ($stmtHistorico->affected_rows === 0) {
                throw new Exception("Erro ao registrar no histórico.");
            }

            return [
                "status" => "success",
                "message" => "Empréstimo aprovado com sucesso!",
                "id_transacao" => $id,
                "valor" => $valor,
                "saldo_anterior" => $saldoAtual,
                "saldo_novo" => $novoSaldo,
                "parcelas" => $parcelas,
                "taxa_juros" => $taxaJuros . "%"
            ];

        } catch (Exception $e) {
            return [
                "status" => "error",
                "message" => $e->getMessage()
            ];
        }
    }
}
?>