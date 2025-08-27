<footer class="footer">
    <div class="footer-container">
        <div class="footer-section">
            <h3>Tentang Kami</h3>
            <p>Bulefotokopi adalah penyedia layanan fotokopi dan percetakan terpercaya dengan kualitas terbaik dan pelayanan yang memuaskan.</p>
        </div>
        
        <div class="footer-section">
            <h3>Layanan</h3>
            <ul>
                <li><a href="services.php">Fotokopi</a></li>
                <li><a href="print.php">Cetak Dokumen</a></li>
                <li><a href="gallery.php">Galeri</a></li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h3>Kontak</h3>
            <ul class="contact-info">
                <li>
                    <i class="fas fa-map-marker-alt"></i>
                    Jl. Contoh No. 123, Kota
                </li>
                <li>
                    <i class="fas fa-phone"></i>
                    (021) 1234-5678
                </li>
                <li>
                    <i class="fas fa-envelope"></i>
                    info@bulefotokopi.com
                </li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h3>Ikuti Kami</h3>
            <div class="social-links">
                <a href="#" target="_blank"><i class="fab fa-facebook"></i></a>
                <a href="#" target="_blank"><i class="fab fa-instagram"></i></a>
                <a href="#" target="_blank"><i class="fab fa-twitter"></i></a>
                <a href="#" target="_blank"><i class="fab fa-whatsapp"></i></a>
            </div>
        </div>
    </div>
    
    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> Bulefotokopi. All rights reserved.</p>
    </div>
</footer>

<style>
.footer {
    background: #eaf2ff;
    color: #3576d3;
    padding: 3rem 0 1rem;
    margin-top: 3rem;
}

.footer-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}

.footer-section h3 {
    color: #3576d3;
    margin-bottom: 1.5rem;
    font-size: 1.2rem;
    position: relative;
    padding-bottom: 0.5rem;
}

.footer-section h3::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 50px;
    height: 2px;
    background: #4f8cff;
}

.footer-section p {
    color: #5a5a7a;
    line-height: 1.6;
}

.footer-section ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-section ul li {
    margin-bottom: 0.8rem;
}

.footer-section ul li a {
    color: #3576d3;
    text-decoration: none;
    transition: color 0.3s;
}

.footer-section ul li a:hover {
    color: #4f8cff;
}

.contact-info li {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #5a5a7a;
}

.social-links {
    display: flex;
    gap: 1rem;
}

.social-links a {
    color: #3576d3;
    font-size: 1.5rem;
    transition: color 0.3s;
}

.social-links a:hover {
    color: #4f8cff;
}

.footer-bottom {
    text-align: center;
    padding-top: 2rem;
    margin-top: 2rem;
    border-top: 1px solid #d0e2ff;
}

.footer-bottom p {
    color: #5a5a7a;
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .footer {
        padding: 2rem 0 1rem;
    }
    
    .footer-container {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .footer-section {
        text-align: center;
    }
    
    .footer-section h3::after {
        left: 50%;
        transform: translateX(-50%);
    }
    
    .contact-info li {
        justify-content: center;
    }
    
    .social-links {
        justify-content: center;
    }
}
</style> 