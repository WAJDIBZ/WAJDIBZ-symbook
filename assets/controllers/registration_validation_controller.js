import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        "form", "nomInput", "prenomInput", "emailInput", "passwordInput", 
        "confirmPasswordInput", "passwordStrength", "submitButton"
    ];

    connect() {
        this.validateInputsOnChange();
        this.setupPasswordStrengthMeter();
    }

    validateInputsOnChange() {
        // Ajouter des écouteurs d'événements pour la validation en temps réel
        this.nomInputTarget.addEventListener('input', () => this.validateName(this.nomInputTarget));
        this.prenomInputTarget.addEventListener('input', () => this.validateName(this.prenomInputTarget));
        this.emailInputTarget.addEventListener('input', () => this.validateEmail());
        this.passwordInputTarget.addEventListener('input', () => this.validatePassword());
        this.confirmPasswordInputTarget.addEventListener('input', () => this.validatePasswordMatch());
    }

    setupPasswordStrengthMeter() {
        // Setup the password strength meter
        this.passwordInputTarget.addEventListener('input', () => {
            const password = this.passwordInputTarget.value;
            const strength = this.calculatePasswordStrength(password);
            this.updatePasswordStrengthUI(strength);
        });
    }

    validateName(inputElement) {
        const name = inputElement.value.trim();
        const isValid = name.length >= 2 && /^[a-zA-ZÀ-ÿ\s\'-]+$/.test(name);
        
        if (!isValid) {
            inputElement.classList.add('border-red-500');
            inputElement.classList.remove('border-green-500');
        } else {
            inputElement.classList.remove('border-red-500');
            inputElement.classList.add('border-green-500');
        }
        
        return isValid;
    }

    validateEmail() {
        const email = this.emailInputTarget.value.trim();
        const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        const isValid = emailRegex.test(email);
        
        if (!isValid) {
            this.emailInputTarget.classList.add('border-red-500');
            this.emailInputTarget.classList.remove('border-green-500');
        } else {
            this.emailInputTarget.classList.remove('border-red-500');
            this.emailInputTarget.classList.add('border-green-500');
        }
        
        return isValid;
    }

    validatePassword() {
        const password = this.passwordInputTarget.value;
        // Au moins 8 caractères, 1 majuscule, 1 minuscule, 1 chiffre et 1 caractère spécial
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
        const isValid = passwordRegex.test(password);
        
        if (!isValid) {
            this.passwordInputTarget.classList.add('border-red-500');
            this.passwordInputTarget.classList.remove('border-green-500');
        } else {
            this.passwordInputTarget.classList.remove('border-red-500');
            this.passwordInputTarget.classList.add('border-green-500');
        }
        
        this.validatePasswordMatch();
        return isValid;
    }

    validatePasswordMatch() {
        const password = this.passwordInputTarget.value;
        const confirmPassword = this.confirmPasswordInputTarget.value;
        const isValid = password === confirmPassword && password !== '';
        
        if (!isValid) {
            this.confirmPasswordInputTarget.classList.add('border-red-500');
            this.confirmPasswordInputTarget.classList.remove('border-green-500');
        } else {
            this.confirmPasswordInputTarget.classList.remove('border-red-500');
            this.confirmPasswordInputTarget.classList.add('border-green-500');
        }
        
        return isValid;
    }

    calculatePasswordStrength(password) {
        // Algorithme simplifié pour évaluer la force du mot de passe
        let score = 0;
        
        if (password.length > 7) score += 1;
        if (password.length > 10) score += 1;
        if (/[A-Z]/.test(password)) score += 1;
        if (/[a-z]/.test(password)) score += 1;
        if (/\d/.test(password)) score += 1;
        if (/[^A-Za-z0-9]/.test(password)) score += 1;
        
        return score;
    }

    updatePasswordStrengthUI(strength) {
        const meter = this.passwordStrengthTarget;
        
        // Retirer toutes les classes existantes
        meter.className = 'h-2 mt-1 rounded transition-all';
        
        switch (strength) {
            case 0:
            case 1:
                meter.classList.add('w-1/4', 'bg-red-500');
                meter.setAttribute('title', 'Très faible');
                break;
            case 2:
            case 3:
                meter.classList.add('w-2/4', 'bg-yellow-500');
                meter.setAttribute('title', 'Moyen');
                break;
            case 4:
            case 5:
                meter.classList.add('w-3/4', 'bg-blue-500');
                meter.setAttribute('title', 'Fort');
                break;
            case 6:
                meter.classList.add('w-full', 'bg-green-500');
                meter.setAttribute('title', 'Très fort');
                break;
        }
    }
    
    validateForm(event) {
        // Validation complète du formulaire lors de la soumission
        const isNameValid = this.validateName(this.nomInputTarget);
        const isPrenomValid = this.validateName(this.prenomInputTarget);
        const isEmailValid = this.validateEmail();
        const isPasswordValid = this.validatePassword();
        const isPasswordMatch = this.validatePasswordMatch();
        
        if (!(isNameValid && isPrenomValid && isEmailValid && isPasswordValid && isPasswordMatch)) {
            event.preventDefault();
            // Afficher un message d'erreur global
            const errorMessage = 'Veuillez corriger les erreurs dans le formulaire avant de continuer.';
            
            // Créer un élément d'alerte si nécessaire
            if (!document.getElementById('form-error-alert')) {
                const alert = document.createElement('div');
                alert.id = 'form-error-alert';
                alert.className = 'mb-4 rounded-md bg-red-50 p-4 border border-red-200';
                alert.innerHTML = `
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800">${errorMessage}</p>
                        </div>
                    </div>
                `;
                this.formTarget.prepend(alert);
            }
        }
    }
}
