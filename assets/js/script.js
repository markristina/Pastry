// Navigation Active State + Admin dashboard helpers
document.addEventListener('DOMContentLoaded', () => {
    // Highlight active top navigation link on public pages
    const currentLocation = location.href;
    const menuItem = document.querySelectorAll('.nav-links a');
    const menuLength = menuItem.length;
    for (let i = 0; i < menuLength; i++) {
        if (menuItem[i].href === currentLocation) {
            menuItem[i].className = "active";
        }
    }

    // Admin: sidebar active state on click
    const adminSidebarLinks = document.querySelectorAll('.admin-sidebar__nav a[href^="#"]');
    adminSidebarLinks.forEach(link => {
        link.addEventListener('click', () => {
            adminSidebarLinks.forEach(l => l.classList.remove('active'));
            link.classList.add('active');
        });
    });

    // Admin: simple search filter for orders and users tables
    const adminSearchInput = document.querySelector('.admin-search input[type="search"]');
    if (adminSearchInput) {
        const ordersBody = document.querySelector('#orders .admin-card--table .admin-table tbody');
        const usersBody = document.querySelector('#users .admin-card--table .admin-table tbody');

        const filterTable = (tbody, term) => {
            if (!tbody) return;
            const rows = tbody.querySelectorAll('tr');
            const value = term.trim().toLowerCase();
            rows.forEach(row => {
                if (!value) {
                    row.style.display = '';
                    return;
                }
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(value) ? '' : 'none';
            });
        };

        adminSearchInput.addEventListener('input', () => {
            const term = adminSearchInput.value;
            filterTable(ordersBody, term);
            filterTable(usersBody, term);
        });
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

// Smooth scrolling for same-page anchor links only
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        if (href === '#') return;
        const target = document.querySelector(href);
        if (target && href.length > 1) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth' });
        }
    });
});

// Reveal on scroll
const observers = document.querySelectorAll('.fade-up');
if ('IntersectionObserver' in window && observers.length) {
    const io = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.2,
        rootMargin: '0px 0px -50px 0px'
    });

    observers.forEach(el => io.observe(el));
} else {
    observers.forEach(el => el.classList.add('visible'));
}

// Newsletter subscription handler
function handleNewsletter(event) {
    event.preventDefault();
    const emailInput = document.getElementById('newsletterEmail');
    const email = emailInput ? emailInput.value : '';
    if (email) {
        alert(`Thanks for subscribing! Updates will be sent to ${email}.`);
        emailInput.value = '';
    } else {
        alert('Please enter a valid email address.');
    }
    return false;
}
