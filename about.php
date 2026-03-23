<?php
$pageTitle = 'About Us';
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>About Bayawan Bai Hotel</h1>
        <p>Discover our story and commitment to excellence</p>
    </div>
</div>

<!-- About Hero -->
<section style="padding: 80px 0; background-color: var(--light-color);">
    <div class="container">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center;">
            <div>
                <p style="color: var(--primary-color); font-weight: 600; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 15px;">Our Story</p>
                <h2 style="font-size: 42px; margin-bottom: 25px;">A Legacy of Hospitality</h2>
                <p style="font-size: 16px; color: #666; line-height: 1.8; margin-bottom: 20px;">
                    Bayawan Bai Hotel was founded with a vision to bring world-class hospitality to the beautiful coastal city of Bayawan in Negros Oriental. Named after the city's warm and welcoming spirit ("Bai" means friend in the local dialect), our hotel has become a landmark destination for travelers seeking both adventure and relaxation.
                </p>
                <p style="font-size: 16px; color: #666; line-height: 1.8; margin-bottom: 20px;">
                    Since our opening in 2009, we have been committed to showcasing the best of Filipino hospitality while providing modern comforts and amenities. Our location offers easy access to the region's natural wonders, including pristine beaches, mountain trails, and marine sanctuaries.
                </p>
                <div style="display: flex; gap: 40px; margin-top: 30px;">
                    <div>
                        <h3 style="font-size: 36px; color: var(--primary-color); margin-bottom: 5px;">15+</h3>
                        <p style="font-size: 14px; color: #666;">Years of Excellence</p>
                    </div>
                    <div>
                        <h3 style="font-size: 36px; color: var(--primary-color); margin-bottom: 5px;">50+</h3>
                        <p style="font-size: 14px; color: #666;">Luxury Rooms</p>
                    </div>
                    <div>
                        <h3 style="font-size: 36px; color: var(--primary-color); margin-bottom: 5px;">100+</h3>
                        <p style="font-size: 14px; color: #666;">Team Members</p>
                    </div>
                </div>
            </div>
            <div style="position: relative;">
                <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Hotel Exterior" style="width: 100%; border-radius: 10px; box-shadow: 0 20px 40px rgba(0,0,0,0.15);">
                <div style="position: absolute; bottom: -30px; left: -30px; background-color: var(--primary-color); color: white; padding: 30px; border-radius: 10px;">
                    <p style="font-size: 14px; margin-bottom: 10px;">Established</p>
                    <p style="font-size: 24px; font-weight: 700;">2009</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Mission & Vision -->
<section style="padding: 80px 0; background-color: var(--gray-light);">
    <div class="container">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
            <div style="background-color: white; padding: 50px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                <i class="fas fa-bullseye" style="font-size: 50px; color: var(--primary-color); margin-bottom: 25px;"></i>
                <h3 style="font-size: 28px; margin-bottom: 20px;">Our Mission</h3>
                <p style="font-size: 16px; color: #666; line-height: 1.8;">
                    To provide exceptional hospitality experiences that celebrate the natural beauty and cultural heritage of Bayawan City while ensuring every guest feels welcomed, valued, and inspired to return.
                </p>
            </div>
            <div style="background-color: white; padding: 50px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                <i class="fas fa-eye" style="font-size: 50px; color: var(--primary-color); margin-bottom: 25px;"></i>
                <h3 style="font-size: 28px; margin-bottom: 20px;">Our Vision</h3>
                <p style="font-size: 16px; color: #666; line-height: 1.8;">
                    To be the premier destination for travelers seeking authentic Filipino hospitality in Negros Oriental, recognized for our commitment to sustainability, community engagement, and service excellence.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Core Values -->
<section style="padding: 80px 0; background-color: var(--light-color);">
    <div class="container">
        <div style="text-align: center; margin-bottom: 50px;">
            <p style="color: var(--primary-color); font-weight: 600; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 15px;">What We Stand For</p>
            <h2 style="font-size: 42px; margin-bottom: 20px;">Our Core Values</h2>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 30px;">
            <div style="text-align: center; padding: 40px 30px; background-color: var(--gray-light); border-radius: 10px; transition: all 0.3s;" onmouseover="this.style.transform='translateY(-10px)'; this.style.boxShadow='0 15px 40px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                <i class="fas fa-heart" style="font-size: 40px; color: var(--primary-color); margin-bottom: 20px;"></i>
                <h4 style="font-size: 20px; margin-bottom: 15px;">Hospitality</h4>
                <p style="font-size: 14px; color: #666; line-height: 1.6;">We welcome every guest with genuine warmth and Filipino hospitality.</p>
            </div>
            <div style="text-align: center; padding: 40px 30px; background-color: var(--gray-light); border-radius: 10px; transition: all 0.3s;" onmouseover="this.style.transform='translateY(-10px)'; this.style.boxShadow='0 15px 40px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                <i class="fas fa-award" style="font-size: 40px; color: var(--primary-color); margin-bottom: 20px;"></i>
                <h4 style="font-size: 20px; margin-bottom: 15px;">Excellence</h4>
                <p style="font-size: 14px; color: #666; line-height: 1.6;">We strive for excellence in every aspect of our service and facilities.</p>
            </div>
            <div style="text-align: center; padding: 40px 30px; background-color: var(--gray-light); border-radius: 10px; transition: all 0.3s;" onmouseover="this.style.transform='translateY(-10px)'; this.style.boxShadow='0 15px 40px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                <i class="fas fa-leaf" style="font-size: 40px; color: var(--primary-color); margin-bottom: 20px;"></i>
                <h4 style="font-size: 20px; margin-bottom: 15px;">Sustainability</h4>
                <p style="font-size: 14px; color: #666; line-height: 1.6;">We are committed to protecting the environment and supporting our community.</p>
            </div>
            <div style="text-align: center; padding: 40px 30px; background-color: var(--gray-light); border-radius: 10px; transition: all 0.3s;" onmouseover="this.style.transform='translateY(-10px)'; this.style.boxShadow='0 15px 40px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                <i class="fas fa-users" style="font-size: 40px; color: var(--primary-color); margin-bottom: 20px;"></i>
                <h4 style="font-size: 20px; margin-bottom: 15px;">Community</h4>
                <p style="font-size: 14px; color: #666; line-height: 1.6;">We actively engage with and support the local Bayawan community.</p>
            </div>
        </div>
    </div>
</section>

<!-- Team Section -->
<section style="padding: 80px 0; background-color: var(--gray-light);">
    <div class="container">
        <div style="text-align: center; margin-bottom: 50px;">
            <p style="color: var(--primary-color); font-weight: 600; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 15px;">Our Team</p>
            <h2 style="font-size: 42px; margin-bottom: 20px;">Meet the Leadership</h2>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 30px;">
            <div style="text-align: center;">
                <div style="width: 150px; height: 150px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; color: white; font-size: 50px;">
                    <i class="fas fa-user"></i>
                </div>
                <h4 style="font-size: 20px; margin-bottom: 5px;">Maria Santos</h4>
                <p style="color: var(--primary-color); font-size: 14px; margin-bottom: 10px;">General Manager</p>
                <p style="font-size: 13px; color: #666; line-height: 1.6;">With 20 years of hospitality experience, Maria leads our team with passion and dedication.</p>
            </div>
            <div style="text-align: center;">
                <div style="width: 150px; height: 150px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; color: white; font-size: 50px;">
                    <i class="fas fa-user"></i>
                </div>
                <h4 style="font-size: 20px; margin-bottom: 5px;">Juan Dela Cruz</h4>
                <p style="color: var(--primary-color); font-size: 14px; margin-bottom: 10px;">Operations Director</p>
                <p style="font-size: 13px; color: #666; line-height: 1.6;">Juan ensures every aspect of our hotel operations runs smoothly and efficiently.</p>
            </div>
            <div style="text-align: center;">
                <div style="width: 150px; height: 150px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; color: white; font-size: 50px;">
                    <i class="fas fa-user"></i>
                </div>
                <h4 style="font-size: 20px; margin-bottom: 5px;">Elena Reyes</h4>
                <p style="color: var(--primary-color); font-size: 14px; margin-bottom: 10px;">Executive Chef</p>
                <p style="font-size: 13px; color: #666; line-height: 1.6;">Elena brings the flavors of Negros Oriental to life through her creative culinary expertise.</p>
            </div>
            <div style="text-align: center;">
                <div style="width: 150px; height: 150px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; color: white; font-size: 50px;">
                    <i class="fas fa-user"></i>
                </div>
                <h4 style="font-size: 20px; margin-bottom: 5px;">Carlos Mendoza</h4>
                <p style="color: var(--primary-color); font-size: 14px; margin-bottom: 10px;">Guest Relations Manager</p>
                <p style="font-size: 13px; color: #666; line-height: 1.6;">Carlos ensures every guest receives personalized attention and memorable experiences.</p>
            </div>
        </div>
    </div>
</section>

<!-- Local Attractions -->
<section style="padding: 80px 0; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));">
    <div class="container" style="text-align: center;">
        <h2 style="color: white; font-size: 42px; margin-bottom: 15px;">Explore Bayawan City</h2>
        <p style="color: rgba(255,255,255,0.9); font-size: 18px; margin-bottom: 40px; max-width: 700px; margin-left: auto; margin-right: auto;">
            Our hotel is perfectly situated to help you discover the natural wonders of Negros Oriental
        </p>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px;">
            <div style="background-color: rgba(255,255,255,0.1); backdrop-filter: blur(10px); padding: 40px; border-radius: 10px; color: white;">
                <i class="fas fa-water" style="font-size: 40px; margin-bottom: 20px;"></i>
                <h4 style="font-size: 22px; margin-bottom: 15px; color: white;">Danjugan Island</h4>
                <p style="font-size: 15px; line-height: 1.6; opacity: 0.9;">A marine sanctuary offering world-class diving and snorkeling experiences just a short boat ride away.</p>
            </div>
            <div style="background-color: rgba(255,255,255,0.1); backdrop-filter: blur(10px); padding: 40px; border-radius: 10px; color: white;">
                <i class="fas fa-mountain" style="font-size: 40px; margin-bottom: 20px;"></i>
                <h4 style="font-size: 22px; margin-bottom: 15px; color: white;">Mt. Talinis</h4>
                <p style="font-size: 15px; line-height: 1.6; opacity: 0.9;">The "Cuernos de Negros" offers challenging hikes and breathtaking views of the region.</p>
            </div>
            <div style="background-color: rgba(255,255,255,0.1); backdrop-filter: blur(10px); padding: 40px; border-radius: 10px; color: white;">
                <i class="fas fa-umbrella-beach" style="font-size: 40px; margin-bottom: 20px;"></i>
                <h4 style="font-size: 22px; margin-bottom: 15px; color: white;">Bayawan Bay</h4>
                <p style="font-size: 15px; line-height: 1.6; opacity: 0.9;">Pristine beaches perfect for swimming, sunbathing, and enjoying spectacular sunsets.</p>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
