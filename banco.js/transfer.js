class Transfer {
    constructor(sender, receiver, value) {
        this.sender = sender;
        this.receiver = receiver;
        this.value = value;
        this.createdAt = new Date();
    }
}

/**
 * ============================================
 * FUNÇÕES UTILITÁRIAS
 * ============================================
 */

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}

function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('pt-BR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    }).format(date);
}

/**
 * ============================================
 * LÓGICA DA PÁGINA DE TRANSFERÊNCIA
 * ============================================
 */

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('transferForm');

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const fromEmail = document.getElementById('fromEmail').value.trim();
        const toEmail = document.getElementById('toEmail').value.trim();
        const value = parseFloat(document.getElementById('value').value);

        // Validações
        if (!fromEmail || !toEmail || !value) {
            showNotification('Preencha todos os campos', 'error');
            return;
        }

        if (fromEmail === toEmail) {
            showNotification('Não é possível transferir para a mesma conta', 'error');
            return;
        }

        if (value <= 0) {
            showNotification('O valor deve ser maior que zero', 'error');
            return;
        }

        // Prepara os dados para enviar
        const formData = new FormData();
        formData.append('fromEmail', fromEmail);
        formData.append('toEmail', toEmail);
        formData.append('valor', value);

        try {
            // Faz a requisição para o PHP
            const response = await fetch('../banco.php/transfer.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.status === 'success') {
                showNotification(
                    `Transferência de ${formatCurrency(value)} realizada com sucesso para ${toEmail}!`,
                    'success'
                );
                
                form.reset();
                
                setTimeout(() => {
                    window.location.href = 'index.html';
                }, 2000);
            } else {
                showNotification(data.message, 'error');
            }
        } catch (error) {
            console.error("Erro:", error);
            showNotification('Erro ao conectar com o servidor. Verifique se o XAMPP está rodando.', 'error');
        }
    });
});