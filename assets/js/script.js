// Navigation Active State
document.addEventListener('DOMContentLoaded', () => {
    const currentLocation = location.href;
    const menuItem = document.querySelectorAll('.nav-links a');
    const menuLength = menuItem.length;
    for (let i = 0; i < menuLength; i++) {
        if (menuItem[i].href === currentLocation) {
            menuItem[i].className = "active";
        }
    }
});

// Login Handler
function handleLogin(event) {
    event.preventDefault();
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;

    // Simple validation simulation
    if (email && password) {
        alert(`Welcome back, ${email}! You have successfully logged in.`);
        // In a real app, you would redirect here
        window.location.href = 'index.html';
    } else {
        alert('Please fill in all fields.');
    }
    return false;
}

// Signup Handler
function handleSignup(event) {
    event.preventDefault();
    const fullname = document.getElementById('fullname').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm-password').value;
    const role = document.getElementById('role').value;
    const terms = document.getElementById('terms').checked;

    if (!terms) {
        alert('You must agree to the Terms of Service and Privacy Policy to sign up.');
        return false;
    }

    if (password !== confirmPassword) {
        alert('Passwords do not match!');
        return false;
    }

    if (fullname && email && password && role) {
        const roleText = role === 'bakery_owner' ? 'Bakery Owner' : 'Customer';
        alert(`Thank you for signing up as a ${roleText}, ${fullname}! Your account has been created.`);
        // In a real app, you would redirect here
        window.location.href = 'login.html';
    } else {
        alert('Please fill in all fields.');
    }
    return false;
}

// Contact Form Handler
function handleContact(event) {
    event.preventDefault();
    const name = document.getElementById('name').value;
    const email = document.getElementById('email').value;
    const message = document.getElementById('message').value;

    if (name && email && message) {
        alert(`Thank you, ${name}! We have received your message and will get back to you soon.`);
        document.getElementById('contactForm').reset();
    } else {
        alert('Please fill in all fields.');
    }
    return false;
}

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();

        document.querySelector(this.getAttribute('href')).scrollIntoView({
            behavior: 'smooth'
        });
    });
});