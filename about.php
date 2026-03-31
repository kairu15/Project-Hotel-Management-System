<?php
require_once 'includes/header.php';
$pageTitle = __('About Us');
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><?php echo __('About Bayawan Bai Hotel'); ?></h1>
        <p><?php echo __('Discover our story and commitment to excellence'); ?></p>
    </div>
</div>

<!-- About Hero -->
<section style="padding: 80px 0; background-color: var(--light-color);">
    <div class="container">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center;">
            <div>
                <p style="color: var(--primary-color); font-weight: 600; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 15px;"><?php echo __('Our Story'); ?></p>
                <h2 style="font-size: 42px; margin-bottom: 25px;"><?php echo __('A Legacy of Hospitality'); ?></h2>
                <p style="font-size: 16px; color: #666; line-height: 1.8; margin-bottom: 20px;">
                    Bayawan Bai Hotel was founded with a vision to bring world-class hospitality to the beautiful coastal city of Bayawan in Negros Oriental. Named after the city's warm and welcoming spirit ("Bai" means friend in the local dialect), our hotel has become a landmark destination for travelers seeking both adventure and relaxation.
                </p>
                <p style="font-size: 16px; color: #666; line-height: 1.8; margin-bottom: 20px;">
                    Since our opening in 2009, we have been committed to showcasing the best of Filipino hospitality while providing modern comforts and amenities. Our location offers easy access to the region's natural wonders, including pristine beaches, mountain trails, and marine sanctuaries.
                </p>
                <div style="display: flex; gap: 40px; margin-top: 30px;">
                    <div>
                        <h3 style="font-size: 36px; color: var(--primary-color); margin-bottom: 5px;">15+</h3>
                        <p style="font-size: 14px; color: #666;"><?php echo __('Years of Excellence'); ?></p>
                    </div>
                    <div>
                        <h3 style="font-size: 36px; color: var(--primary-color); margin-bottom: 5px;">50+</h3>
                        <p style="font-size: 14px; color: #666;"><?php echo __('Luxury Rooms'); ?></p>
                    </div>
                    <div>
                        <h3 style="font-size: 36px; color: var(--primary-color); margin-bottom: 5px;">100+</h3>
                        <p style="font-size: 14px; color: #666;"><?php echo __('Team Members'); ?></p>
                    </div>
                </div>
            </div>
            <div style="position: relative;">
                <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Hotel Exterior" style="width: 100%; border-radius: 10px; box-shadow: 0 20px 40px rgba(0,0,0,0.15);">
                <div style="position: absolute; bottom: -30px; left: -30px; background-color: var(--primary-color); color: white; padding: 30px; border-radius: 10px;">
                    <p style="font-size: 14px; margin-bottom: 10px;"><?php echo __('Established'); ?></p>
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
<section style="padding: 80px 0; background-color: var(--light-color);">
    <div class="container">
        <div style="text-align: center; margin-bottom: 50px;">
            <p style="color: var(--primary-color); font-weight: 600; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 15px;">Explore</p>
            <h2 style="font-size: 42px; margin-bottom: 20px;">Bayawan City Attractions</h2>
            <p style="font-size: 16px; color: #666; max-width: 700px; margin: 0 auto;">Discover the best spots in Bayawan — from city landmarks to nature escapes</p>
        </div>

        <!-- City Proper / Easy-to-Access Spots -->
        <div style="margin-bottom: 60px;">
            <h3 style="font-size: 28px; margin-bottom: 30px; display: flex; align-items: center; gap: 15px;">
                <i class="fas fa-city" style="color: var(--primary-color);"></i>
                City Proper / Easy-to-Access Spots
            </h3>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px;">
                <div style="background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                    <h4 style="font-size: 20px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-tree" style="color: var(--primary-color);"></i>
                        Bayawan City Plaza
                    </h4>
                    <p style="font-size: 15px; color: #666; line-height: 1.7; margin-bottom: 10px;">The heart of the city. Perfect for chill walks, jogging, and hangouts at night. Features events, lights, and local vibe. Considered a major landmark and community hub.</p>
                    <p style="font-size: 14px; color: var(--primary-color);"><i class="fas fa-clock" style="margin-right: 8px;"></i>Best time: late afternoon to evening</p>
                </div>
                <div style="background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                    <h4 style="font-size: 20px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-water" style="color: var(--primary-color);"></i>
                        Bayawan City Boulevard
                    </h4>
                    <p style="font-size: 15px; color: #666; line-height: 1.7; margin-bottom: 10px;">Coastal road with a nice sea view. Good for sunset watching, relaxing, and street food. One of the most visited public spots in the city.</p>
                    <p style="font-size: 14px; color: var(--primary-color);"><i class="fas fa-camera" style="margin-right: 8px;"></i>Best for: sunset photos</p>
                </div>
                <div style="background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                    <h4 style="font-size: 20px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-landmark" style="color: var(--primary-color);"></i>
                        Bayawan City Hall
                    </h4>
                    <p style="font-size: 15px; color: #666; line-height: 1.7; margin-bottom: 10px;">Not just government — also a clean, aesthetic area. Nice for photos especially at night with lights illuminating the building.</p>
                </div>
                <div style="background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                    <h4 style="font-size: 20px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-shopping-basket" style="color: var(--primary-color);"></i>
                        Bayawan City Public Market
                    </h4>
                    <p style="font-size: 15px; color: #666; line-height: 1.7; margin-bottom: 10px;">Experience local life and food trip. Fresh seafood, street foods, and local delicacies. Important economic hub of the city.</p>
                </div>
            </div>
        </div>

        <!-- Nature Spots -->
        <div style="margin-bottom: 60px;">
            <h3 style="font-size: 28px; margin-bottom: 30px; display: flex; align-items: center; gap: 15px;">
                <i class="fas fa-leaf" style="color: var(--primary-color);"></i>
                Nature Spots (Near Proper but Worth It)
            </h3>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px;">
                <div style="background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                    <h4 style="font-size: 20px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-water" style="color: var(--primary-color);"></i>
                        Niludhan Falls
                    </h4>
                    <p style="font-size: 15px; color: #666; line-height: 1.7; margin-bottom: 10px;">One of the most famous attractions in Bayawan. Clear water and relaxing environment. Short trek (10–15 minutes) before reaching the falls.</p>
                    <p style="font-size: 14px; color: var(--primary-color);"><i class="fas fa-swimmer" style="margin-right: 8px;"></i>Best for: swimming and barkada trips</p>
                </div>
                <div style="background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                    <h4 style="font-size: 20px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-tint" style="color: var(--primary-color);"></i>
                        Mantapi Falls
                    </h4>
                    <p style="font-size: 15px; color: #666; line-height: 1.7; margin-bottom: 10px;">Closer to the city (around 4 km). Natural swimming pool vibe. Great for a quick getaway from the city proper.</p>
                </div>
                <div style="background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                    <h4 style="font-size: 20px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-water" style="color: var(--primary-color);"></i>
                        Lourdes Falls
                    </h4>
                    <p style="font-size: 15px; color: #666; line-height: 1.7; margin-bottom: 10px;">Multi-level waterfall. Peaceful and less crowded. Good for picnics with family and friends.</p>
                </div>
                <div style="background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                    <h4 style="font-size: 20px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-mountain" style="color: var(--primary-color);"></i>
                        Cabuag Falls
                    </h4>
                    <p style="font-size: 15px; color: #666; line-height: 1.7; margin-bottom: 10px;">Tall and more "hidden" waterfall. Less touristy, more adventure feel. Perfect for those seeking tranquility.</p>
                </div>
            </div>
        </div>

        <!-- Chill & Scenic Spots -->
        <div style="margin-bottom: 60px;">
            <h3 style="font-size: 28px; margin-bottom: 30px; display: flex; align-items: center; gap: 15px;">
                <i class="fas fa-camera-retro" style="color: var(--primary-color);"></i>
                Chill and Scenic Spots
            </h3>
            <div style="background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                <h4 style="font-size: 20px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-tractor" style="color: var(--primary-color);"></i>
                    Rice Fields / Countryside Views
                </h4>
                <p style="font-size: 15px; color: #666; line-height: 1.7; margin-bottom: 15px;">Bayawan is known as an agricultural city with wide farmlands. Perfect for:</p>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-wrap: wrap; gap: 20px;">
                    <li style="display: flex; align-items: center; gap: 8px; font-size: 15px; color: #666;"><i class="fas fa-car" style="color: var(--primary-color);"></i>Road trips</li>
                    <li style="display: flex; align-items: center; gap: 8px; font-size: 15px; color: #666;"><i class="fas fa-sun" style="color: var(--primary-color);"></i>Sunset drives</li>
                    <li style="display: flex; align-items: center; gap: 8px; font-size: 15px; color: #666;"><i class="fas fa-image" style="color: var(--primary-color);"></i>Photography</li>
                </ul>
            </div>
        </div>

        <!-- Quick Recommendations -->
        <div style="margin-bottom: 60px;">
            <h3 style="font-size: 28px; margin-bottom: 30px; display: flex; align-items: center; gap: 15px;">
                <i class="fas fa-route" style="color: var(--primary-color);"></i>
                Quick Recommendations (if 1-day visit)
            </h3>
            <div style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); padding: 30px; border-radius: 10px; color: white;">
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px;">
                    <div>
                        <h4 style="font-size: 18px; margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-sun" style="font-size: 24px;"></i>
                            Morning
                        </h4>
                        <p style="font-size: 15px; line-height: 1.6; opacity: 0.9;">Mantapi Falls or Niludhan Falls</p>
                    </div>
                    <div>
                        <h4 style="font-size: 18px; margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-cloud-sun" style="font-size: 24px;"></i>
                            Afternoon
                        </h4>
                        <p style="font-size: 15px; line-height: 1.6; opacity: 0.9;">City Plaza + Market</p>
                    </div>
                    <div>
                        <h4 style="font-size: 18px; margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-moon" style="font-size: 24px;"></i>
                            Evening
                        </h4>
                        <p style="font-size: 15px; line-height: 1.6; opacity: 0.9;">Boulevard (sunset + food trip)</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Why Bayawan -->
        <div>
            <h3 style="font-size: 28px; margin-bottom: 30px; display: flex; align-items: center; gap: 15px;">
                <i class="fas fa-thumbs-up" style="color: var(--primary-color);"></i>
                Why Bayawan is Worth Visiting
            </h3>
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                <div style="text-align: center; padding: 25px; background-color: white; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                    <i class="fas fa-user-friends" style="font-size: 30px; color: var(--primary-color); margin-bottom: 15px;"></i>
                    <p style="font-size: 14px; color: #666; line-height: 1.5;">Not crowded (underrated destination)</p>
                </div>
                <div style="text-align: center; padding: 25px; background-color: white; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                    <i class="fas fa-spa" style="font-size: 30px; color: var(--primary-color); margin-bottom: 15px;"></i>
                    <p style="font-size: 14px; color: #666; line-height: 1.5;">Mix of nature + city chill vibe</p>
                </div>
                <div style="text-align: center; padding: 25px; background-color: white; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                    <i class="fas fa-coins" style="font-size: 30px; color: var(--primary-color); margin-bottom: 15px;"></i>
                    <p style="font-size: 14px; color: #666; line-height: 1.5;">Budget-friendly compared to big tourist cities</p>
                </div>
                <div style="text-align: center; padding: 25px; background-color: white; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                    <i class="fas fa-heart" style="font-size: 30px; color: var(--primary-color); margin-bottom: 15px;"></i>
                    <p style="font-size: 14px; color: #666; line-height: 1.5;">Authentic local experience</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
