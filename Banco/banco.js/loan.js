class Loan {
    static #interestRate = 1.05; 

    constructor(value, installmentsCount) {
        this.value = value;
        this.createdAt = new Date();
        this.installments = [];
        
        const totalToPay = value * Loan.#interestRate;
        const installmentValue = totalToPay / installmentsCount;
        
        for (let i = 1; i <= installmentsCount; i++) {
            this.installments.push(new Installment(installmentValue, i));
        }
    }

    static get interestRate() {
        return Loan.#interestRate;
    }

    static set interestRate(percentage) {
        Loan.#interestRate = 1 + (percentage / 100);
    }
}

class Installment {
    constructor(value, number) {
        this.value = value;
        this.number = number;
        this.status = 'pending'; 
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
 * LÓGICA DA PÁGINA DE EMPRÉSTIMO
 * ============================================
 */

document.addEventListener('DOMContentLoaded', function(e) { 
    const form = document.getElementById('loanForm');
    
    if (!form) {
        console.error('Formulário não encontrado');
        return;
    }

    const valueInput = document.getElementById('value');
    const installmentsInput = document.getElementById('installments');
    const interestRateSpan = document.getElementById('interestRate');
    const totalValueSpan = document.getElementById('totalValue');
    const installmentValueSpan = document.getElementById('installmentValue');
    const submitButton = form.querySelector('button[type="submit"]');
    const emailInput = document.getElementById('email');
    const loanForm = document.getElementById('loanForm');

    function updateCalculation() {
        const value = parseFloat(valueInput.value) || 0;
        const installments = parseInt(installmentsInput.value) || 1;
        const interestRate = 5.0;

        interestRateSpan.textContent = interestRate.toFixed(1);

        if (value > 0) {
            const total = value * (1 + interestRate / 100);
            const installmentValue = total / installments;

            totalValueSpan.textContent = total.toFixed(2);
            installmentValueSpan.textContent = installmentValue.toFixed(2);
        } else {
            totalValueSpan.textContent = '0.00';
            installmentValueSpan.textContent = '0.00';
        }
    }

    valueInput.addEventListener('input', updateCalculation);
    installmentsInput.addEventListener('input', updateCalculation);

    updateCalculation();

    // IMPORTANTE: Usar onclick no botão para ter controle total
    loanForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        console.log('teste');

        const email = emailInput.value.trim();
        const value = parseFloat(valueInput.value);
        const installments = parseInt(installmentsInput.value);

        // Validações
        if (!email || !value) {
            showNotification('Preencha todos os campos', 'error');
            return false;
        }

        if (value <= 0) {
            showNotification('O valor deve ser maior que zero', 'error');
            return false;
        }

        if (installments < 1 || installments > 60) {
            showNotification('Número de parcelas inválido (1-60)', 'error');
            return false;
        }

        // Desabilita o botão enquanto processa
        submitButton.disabled = true;
        submitButton.textContent = 'Processando...';

        // Prepara os dados para enviar
        const formData = new FormData();
        formData.append('email', email);
        formData.append('value', value);
        formData.append('installments', installments);

        try {
            // Faz a requisição para o PHP
            const response = await fetch('../banco.php/loan.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.status === 'success') {
                const total = value * (1 + 5 / 100);
                
                showNotification(
                    `Empréstimo de ${formatCurrency(value)} aprovado! Total a pagar: ${formatCurrency(total)}`,
                    'success'
                );
                
                // LIMPA OS CAMPOS DO FORMULÁRIO
                emailInput.value = '';
                valueInput.value = '';
                installmentsInput.value = '12';
                
                // Atualiza o cálculo com os valores zerados
                updateCalculation();
                
            } else {
                showNotification(data.message, 'error');
            }
        } catch (error) {
            console.error("Erro:", error);
            showNotification('Erro ao conectar com o servidor. Verifique se o XAMPP está rodando.', 'error');
        } finally {
            // Reabilita o botão
            submitButton.disabled = false;
            submitButton.textContent = 'Solicitar Empréstimo';
        }

        return false;
    })

    // Impede o submit padrão do formulário
    form.onsubmit = function(e) {
        e.preventDefault();
        e.stopPropagation();
        return false;
    };
});