<footer class="footer" style="background: linear-gradient(to right, #ffffff, #f8fdff); border-top: 2px solid #e6f4ff;">
    <div class="container py-5">
        <div class="row">
            <div class="col-md-4 mb-4 mb-md-0" data-aos="fade-up">
                <h5 class="footer-heading">About Arkan</h5>
                <p class="mb-0 footer-text">Leading provider of attendance management solutions for businesses and organizations.</p>
            </div>
            <div class="col-md-4 mb-4 mb-md-0" data-aos="fade-up" data-aos-delay="100">
                <h5 class="footer-heading">Quick Links</h5>
                <ul class="list-unstyled footer-links">
                    <li><a href="#" class="footer-link"><i class="bi bi-chevron-right me-2"></i>Home</a></li>
                    <li><a href="#" class="footer-link"><i class="bi bi-chevron-right me-2"></i>Features</a></li>
                    <li><a href="#" class="footer-link"><i class="bi bi-chevron-right me-2"></i>Contact</a></li>
                </ul>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <h5 class="footer-heading">Contact Us</h5>
                <ul class="list-unstyled footer-contact">
                    <li>
                        <i class="bi bi-geo-alt me-2" style="color: #2196F3;"></i>
                        <span>123 Business Street, City</span>
                    </li>
                    <li>
                        <i class="bi bi-envelope me-2" style="color: #2196F3;"></i>
                        <a href="mailto:info@arkan.com" class="footer-contact-link">info@arkan.com</a>
                    </li>
                    <li>
                        <i class="bi bi-telephone me-2" style="color: #2196F3;"></i>
                        <a href="tel:+1234567890" class="footer-contact-link">+1 234 567 890</a>
                    </li>
                </ul>
            </div>
        </div>
        <hr class="my-4" style="background-color: #e6f4ff; opacity: 0.5;">
        <div class="text-center" data-aos="fade-up" data-aos-delay="300">
            <p class="mb-0 copyright-text">&copy; {{ date('Y') }} Arkan Attendance System. All rights reserved.</p>
        </div>
    </div>
</footer>

<style>
.footer {
    margin-top: auto;
    box-shadow: 0 -2px 15px rgba(0, 0, 0, 0.04);
}

.footer-heading {
    color: #333;
    font-weight: 600;
    margin-bottom: 1.2rem;
    font-size: 1.1rem;
    position: relative;
    padding-bottom: 0.5rem;
}

.footer-heading::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: 0;
    width: 40px;
    height: 2px;
    background-color: #2196F3;
}

.footer-text {
    color: #666;
    line-height: 1.6;
}

.footer-links li {
    margin-bottom: 0.8rem;
}

.footer-link {
    color: #666;
    text-decoration: none;
    transition: all 0.3s ease;
    display: inline-block;
}

.footer-link:hover {
    color: #2196F3;
    transform: translateX(5px);
}

.footer-contact li {
    margin-bottom: 1rem;
    color: #666;
    display: flex;
    align-items: center;
}

.footer-contact-link {
    color: #666;
    text-decoration: none;
    transition: color 0.3s ease;
}

.footer-contact-link:hover {
    color: #2196F3;
}

.copyright-text {
    color: #78909c;
    font-size: 0.9rem;
}

/* Animation for AOS if not already included */
[data-aos] {
    opacity: 0;
    transition-property: opacity, transform;
}

[data-aos].aos-animate {
    opacity: 1;
}

@media (max-width: 768px) {
    .footer-heading {
        margin-bottom: 1rem;
    }

    .footer-contact li {
        margin-bottom: 0.8rem;
    }
}
</style>
