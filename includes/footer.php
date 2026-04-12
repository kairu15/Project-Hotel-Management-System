    <!-- Footer -->
    <footer style="background-color: var(--dark-color); color: var(--light-color); padding-top: 60px;">
        <div class="container">
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 40px; padding-bottom: 40px; border-bottom: 1px solid rgba(255,255,255,0.1);">
                <!-- Column 1: About -->
                <div>
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                        <img src="assets/bayawanhotellogo.png" alt="Bayawan Bai Hotel Logo" style="width: 50px; height: 50px; object-fit: contain; border-radius: 8px;">
                        <h3 style="color: var(--light-color); font-size: 22px; margin: 0;">Bayawan <span style="color: var(--primary-color);">Bai</span> Hotel</h3>
                    </div>
                    <p style="color: rgba(255,255,255,0.7); font-size: 14px; line-height: 1.8; margin-bottom: 20px;">
                    <?php echo __('Experience luxury and comfort in the heart of Bayawan City. Your perfect escape awaits with stunning views and world-class service.'); ?>
                </p>
                    <div style="display: flex; gap: 15px;">
                        <a href="#" style="width: 40px; height: 40px; background-color: rgba(255,255,255,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--light-color); transition: all 0.3s; text-decoration: none;">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" style="width: 40px; height: 40px; background-color: rgba(255,255,255,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--light-color); transition: all 0.3s; text-decoration: none;">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" style="width: 40px; height: 40px; background-color: rgba(255,255,255,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--light-color); transition: all 0.3s; text-decoration: none;">
                            <i class="fab fa-twitter"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Column 2: Quick Links -->
                <div>
                    <h4 style="color: var(--light-color); font-size: 18px; margin-bottom: 20px;"><?php echo __('Quick Links'); ?></h4>
                    <ul style="list-style: none;">
                        <li style="margin-bottom: 12px;"><a href="/bayawanhotel/rooms.php" style="color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px; transition: color 0.3s;"><?php echo __('Rooms & Suites'); ?></a></li>
                        <li style="margin-bottom: 12px;"><a href="/bayawanhotel/dining.php" style="color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px; transition: color 0.3s;"><?php echo __('Dining'); ?></a></li>
                        <li style="margin-bottom: 12px;"><a href="/bayawanhotel/amenities.php" style="color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px; transition: color 0.3s;"><?php echo __('Amenities'); ?></a></li>
                        <li style="margin-bottom: 12px;"><a href="/bayawanhotel/events.php" style="color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px; transition: color 0.3s;"><?php echo __('Events'); ?></a></li>
                        <li style="margin-bottom: 12px;"><a href="/bayawanhotel/about.php" style="color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px; transition: color 0.3s;"><?php echo __('About Us'); ?></a></li>
                        <li style="margin-bottom: 12px;"><a href="/bayawanhotel/contact.php" style="color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px; transition: color 0.3s;"><?php echo __('Contact'); ?></a></li>
                    </ul>
                </div>
                
                <!-- Column 3: Contact Info -->
                <div>
                    <h4 style="color: var(--light-color); font-size: 18px; margin-bottom: 20px;"><?php echo __('Contact Us'); ?></h4>
                    <ul style="list-style: none;">
                        <li style="margin-bottom: 15px; display: flex; align-items: flex-start; gap: 12px;">
                            <i class="fas fa-map-marker-alt" style="color: var(--primary-color); margin-top: 3px;"></i>
                            <span style="color: rgba(255,255,255,0.7); font-size: 14px; line-height: 1.6;">Bayawan City, Negros Oriental, Philippines 6211</span>
                        </li>
                        <li style="margin-bottom: 15px; display: flex; align-items: center; gap: 12px;">
                            <i class="fas fa-phone" style="color: var(--primary-color);"></i>
                            <span style="color: rgba(255,255,255,0.7); font-size: 14px;">+63 35 123 4567</span>
                        </li>
                        <li style="margin-bottom: 15px; display: flex; align-items: center; gap: 12px;">
                            <i class="fas fa-envelope" style="color: var(--primary-color);"></i>
                            <span style="color: rgba(255,255,255,0.7); font-size: 14px;">bayawanbaiminihotel@gmail.com</span>
                        </li>
                        <li style="display: flex; align-items: center; gap: 12px;">
                            <i class="fas fa-clock" style="color: var(--primary-color);"></i>
                            <span style="color: rgba(255,255,255,0.7); font-size: 14px;"><?php echo __('24/7 Front Desk'); ?></span>
                        </li>
                    </ul>
                </div>
                
                <!-- Column 4: Newsletter & Mobile App -->
                <div>
                    <h4 style="color: var(--light-color); font-size: 18px; margin-bottom: 20px;"><?php echo __('Newsletter'); ?></h4>
                    <p style="color: rgba(255,255,255,0.7); font-size: 14px; line-height: 1.6; margin-bottom: 20px;">
                        <?php echo __('Subscribe to receive special offers and updates.'); ?>
                    </p>
                    <form action="subscribe.php" method="POST" style="display: flex;">
                        <input type="email" name="email" placeholder="<?php echo __('Your email address'); ?>" required style="flex: 1; padding: 12px 15px; border: none; border-radius: 5px 0 0 5px; font-size: 14px; outline: none;">
                        <button type="submit" style="background-color: var(--primary-color); color: var(--light-color); border: none; padding: 12px 20px; border-radius: 0 5px 5px 0; cursor: pointer; transition: background-color 0.3s;">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                    
                    <!-- Mobile App Download -->
                    <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
                        <h4 style="color: var(--light-color); font-size: 16px; margin-bottom: 12px;">
                            <i class="fas fa-mobile-alt" style="color: var(--primary-color); margin-right: 8px;"></i>
                            <?php echo __('Get Our Mobile App'); ?>
                        </h4>
                        <p style="color: rgba(255,255,255,0.6); font-size: 12px; line-height: 1.5; margin-bottom: 12px;">
                            <?php echo __('Book rooms, order food & more on the go!'); ?>
                        </p>
                        <a href="download-app.php" class="btn-download" style="display: inline-flex; align-items: center; gap: 10px; background: linear-gradient(135deg, #3DDC84, #2E7D32); color: white; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; transition: all 0.3s; box-shadow: 0 4px 15px rgba(61, 220, 132, 0.3);">
                            <i class="fab fa-android" style="font-size: 18px;"></i>
                            <span><?php echo __('Download APK'); ?></span>
                        </a>
                        <p style="color: rgba(255,255,255,0.4); font-size: 10px; margin-top: 8px;">
                            <?php echo __('Android 6.0+ required'); ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Bottom Bar -->
            <div style="padding: 20px 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                <p style="color: rgba(255,255,255,0.6); font-size: 13px;">
                    &copy; <?php echo date('Y'); ?> <?php echo __('Bayawan Bai Hotel. All rights reserved.'); ?>
                </p>
                <div style="display: flex; gap: 25px;">
                    <a href="/bayawanhotel/privacy.php" style="color: rgba(255,255,255,0.6); text-decoration: none; font-size: 13px; transition: color 0.3s;"><?php echo __('Privacy Policy'); ?></a>
                    <a href="/bayawanhotel/terms.php" style="color: rgba(255,255,255,0.6); text-decoration: none; font-size: 13px; transition: color 0.3s;"><?php echo __('Terms of Service'); ?></a>
                    <a href="/bayawanhotel/faq.php" style="color: rgba(255,255,255,0.6); text-decoration: none; font-size: 13px; transition: color 0.3s;"><?php echo __('FAQ'); ?></a>
                </div>
            </div>

            <!-- Developer Info -->
            <div style="padding: 15px 0; text-align: center; border-top: 1px solid rgba(255,255,255,0.1);">
                <p style="color: rgba(255,255,255,0.5); font-size: 12px; margin: 0;">
                    <?php echo __('Developed by'); ?> <strong style="color: rgba(255,255,255,0.7);">Kylle Ian D. Acibron</strong>
                    <span style="margin: 0 8px;">•</span>
                    <?php echo __('Contact'); ?>: <a href="mailto:kylleacibron@gmail.com" style="color: rgba(255,255,255,0.6); text-decoration: none;">kylleacibron@gmail.com</a>
                    <span style="margin: 0 8px;">•</span>
                    <?php echo __('Version'); ?>: 1.0.0
                </p>
            </div>
        </div>
    </footer>
    
    <style>
        /* Footer Responsive Styles */
        @media (max-width: 992px) {
            footer > .container > div:first-child {
                grid-template-columns: repeat(2, 1fr);
                gap: 30px;
            }
        }
        
        @media (max-width: 768px) {
            footer {
                padding-top: 40px;
            }
            
            footer > .container > div:first-child {
                grid-template-columns: 1fr;
                gap: 30px;
                text-align: center;
            }
            
            footer > .container > div:first-child > div > div:first-child {
                justify-content: center;
            }
            
            footer > .container > div:first-child > div > div:last-child {
                justify-content: center;
            }
            
            footer > .container > div:first-child > div:nth-child(2) ul,
            footer > .container > div:first-child > div:nth-child(3) ul {
                text-align: left;
                max-width: 300px;
                margin: 0 auto;
            }
            
            footer form {
                max-width: 400px;
                margin: 0 auto;
            }
            
            footer > .container > div:last-child {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            footer > .container > div:last-child > div {
                flex-wrap: wrap;
                justify-content: center;
                gap: 15px;
            }
        }
        
        @media (max-width: 576px) {
            footer {
                padding-top: 30px;
            }
            
            footer > .container > div:first-child {
                gap: 25px;
            }
            
            footer > .container > div:first-child > div h3 {
                font-size: 20px;
            }
            
            footer > .container > div:first-child > div h4 {
                font-size: 16px;
                margin-bottom: 15px;
            }
            
            footer > .container > div:first-child > div p,
            footer > .container > div:first-child > div ul li a,
            footer > .container > div:first-child > div ul li span {
                font-size: 13px;
            }
            
            footer form input {
                padding: 10px 12px;
                font-size: 13px;
            }
            
            footer form button {
                padding: 10px 15px;
            }
            
            footer > .container > div:last-child > p,
            footer > .container > div:last-child > div a {
                font-size: 12px;
            }
        }
        
        /* Download button hover effect */
        footer .btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(61, 220, 132, 0.4) !important;
        }
    </style>
    
    <!-- Scroll to Top Button -->
    <button onclick="scrollToTop()" id="scrollTopBtn" style="display: none; position: fixed; bottom: 30px; right: 30px; width: 50px; height: 50px; background-color: var(--primary-color); color: var(--light-color); border: none; border-radius: 50%; cursor: pointer; font-size: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); transition: all 0.3s; z-index: 999;">
        <i class="fas fa-chevron-up"></i>
    </button>
    
    <script>
        // Scroll to Top Button
        const scrollTopBtn = document.getElementById('scrollTopBtn');
        
        window.onscroll = function() {
            if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
                scrollTopBtn.style.display = 'block';
            } else {
                scrollTopBtn.style.display = 'none';
            }
        };
        
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
    </script>
</body>
</html>
