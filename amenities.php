<?php
$pageTitle = 'Spa & Amenities';
require_once 'includes/header.php';

// Get amenities from database
$db = getDB();
$stmt = $db->query("SELECT * FROM amenities WHERE is_available = 1 ORDER BY category, amenity_name");
$amenities = $stmt->fetchAll();

// Group amenities by category
$groupedAmenities = [];
foreach ($amenities as $amenity) {
    $groupedAmenities[$amenity['category']][] = $amenity;
}

// Category display names
$categoryNames = [
    'spa' => 'Spa & Wellness',
    'gym' => 'Fitness Center',
    'pool' => 'Swimming Pools',
    'wellness' => 'Wellness Activities',
    'other' => 'Other Facilities'
];

$categoryIcons = [
    'spa' => 'fa-spa',
    'gym' => 'fa-dumbbell',
    'pool' => 'fa-swimming-pool',
    'wellness' => 'fa-om',
    'other' => 'fa-concierge-bell'
];
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>Spa & Amenities</h1>
        <p>Relax, rejuvenate, and refresh your senses</p>
    </div>
</div>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <div class="container">
        <ul>
            <li><a href="index.php">Home</a></li>
            <li>/</li>
            <li>Amenities</li>
        </ul>
    </div>
</div>

<!-- Hero Section -->
<section style="padding: 0; position: relative; height: 500px; background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('https://images.unsplash.com/photo-1540555700478-4be289fbecef?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') center/cover no-repeat;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; color: white; max-width: 800px; padding: 0 20px;">
        <p style="font-size: 18px; letter-spacing: 3px; text-transform: uppercase; margin-bottom: 20px;">Wellness & Recreation</p>
        <h2 style="font-size: 48px; color: white; margin-bottom: 25px;">Your Sanctuary Awaits</h2>
        <p style="font-size: 18px; line-height: 1.8; opacity: 0.9;">Indulge in world-class spa treatments, stay active in our fitness center, or simply relax by our stunning infinity pool</p>
    </div>
</section>

<!-- Amenities Overview -->
<section style="padding: 80px 0; background-color: var(--light-color);">
    <div class="container">
        <div style="text-align: center; margin-bottom: 50px;">
            <p style="color: var(--primary-color); font-weight: 600; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 15px;">Facilities</p>
            <h2 style="font-size: 42px; margin-bottom: 20px;">World-Class Amenities</h2>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px;">
            <?php foreach ($groupedAmenities as $category => $items): ?>
            <div style="background-color: var(--gray-light); border-radius: 10px; overflow: hidden; transition: transform 0.3s, box-shadow 0.3s;" onmouseover="this.style.transform='translateY(-10px)'; this.style.boxShadow='0 15px 40px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                <div style="height: 200px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); display: flex; align-items: center; justify-content: center;">
                    <i class="fas <?php echo $categoryIcons[$category] ?? 'fa-star'; ?>" style="font-size: 60px; color: white;"></i>
                </div>
                <div style="padding: 30px;">
                    <h3 style="font-size: 24px; margin-bottom: 15px;"><?php echo $categoryNames[$category] ?? ucfirst($category); ?></h3>
                    <ul style="list-style: none;">
                        <?php foreach (array_slice($items, 0, 4) as $item): ?>
                        <li style="padding: 8px 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 15px;"><i class="fas fa-check" style="color: var(--success-color); margin-right: 10px;"></i><?php echo htmlspecialchars($item['amenity_name']); ?></span>
                            <?php if ($item['price'] > 0): ?>
                            <span style="font-size: 14px; color: var(--primary-color); font-weight: 600;"><?php echo formatPrice($item['price']); ?></span>
                            <?php else: ?>
                            <span style="font-size: 12px; background-color: var(--success-color); color: white; padding: 3px 10px; border-radius: 20px;">Free</span>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                        <?php if (count($items) > 4): ?>
                        <li style="padding-top: 10px; text-align: center;">
                            <span style="font-size: 14px; color: var(--primary-color);">+<?php echo count($items) - 4; ?> more services</span>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Spa Treatments Detail -->
<section style="padding: 80px 0; background-color: var(--gray-light);">
    <div class="container">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center;">
            <div>
                <p style="color: var(--primary-color); font-weight: 600; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 15px;">Spa & Wellness</p>
                <h2 style="font-size: 42px; margin-bottom: 25px;">Rejuvenate Your Senses</h2>
                <p style="font-size: 16px; color: #666; line-height: 1.8; margin-bottom: 20px;">
                    Escape to our tranquil spa sanctuary where expert therapists await to guide you on a journey of relaxation and renewal. Using premium natural products and time-honored techniques, we offer a comprehensive menu of treatments designed to restore balance to your body and mind.
                </p>
                <p style="font-size: 16px; color: #666; line-height: 1.8; margin-bottom: 30px;">
                    From traditional Filipino hilot massages to modern therapeutic treatments, each session is tailored to your individual needs and preferences.
                </p>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <i class="fas fa-check-circle" style="color: var(--success-color); font-size: 20px;"></i>
                        <span>Expert Therapists</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <i class="fas fa-check-circle" style="color: var(--success-color); font-size: 20px;"></i>
                        <span>Premium Products</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <i class="fas fa-check-circle" style="color: var(--success-color); font-size: 20px;"></i>
                        <span>Private Treatment Rooms</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <i class="fas fa-check-circle" style="color: var(--success-color); font-size: 20px;"></i>
                        <span>Couples Packages</span>
                    </div>
                </div>
                
                <a href="contact.php" class="btn btn-primary">Book a Treatment</a>
            </div>
            <div style="position: relative;">
                <img src="https://images.unsplash.com/photo-1544161515-4ab6ce6db874?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" style="width: 100%; border-radius: 10px; box-shadow: 0 20px 40px rgba(0,0,0,0.15);">
                <div style="position: absolute; bottom: -30px; right: -30px; background-color: var(--primary-color); color: white; padding: 30px; border-radius: 10px;">
                    <p style="font-size: 14px; margin-bottom: 5px;">Operating Hours</p>
                    <p style="font-size: 20px; font-weight: 600;">9:00 AM - 9:00 PM</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Pool & Fitness -->
<section style="padding: 80px 0; background-color: var(--light-color);">
    <div class="container">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
            <!-- Infinity Pool -->
            <div style="position: relative; border-radius: 10px; overflow: hidden; height: 500px;">
                <img src="https://images.unsplash.com/photo-1576013551627-0cc20b96c2a7?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" style="width: 100%; height: 100%; object-fit: cover;">
                <div style="position: absolute; inset: 0; background: linear-gradient(transparent, rgba(0,0,0,0.8)); display: flex; flex-direction: column; justify-content: end; padding: 40px;">
                    <h3 style="color: white; font-size: 32px; margin-bottom: 15px;">Infinity Pool</h3>
                    <p style="color: rgba(255,255,255,0.9); font-size: 16px; line-height: 1.7; margin-bottom: 20px;">
                        Our stunning infinity pool offers breathtaking views of Bayawan Bay. Relax on our sun loungers or take a refreshing dip while enjoying the tropical breeze.
                    </p>
                    <div style="display: flex; gap: 30px; color: white;">
                        <span><i class="fas fa-clock" style="color: var(--primary-color); margin-right: 8px;"></i>6:00 AM - 10:00 PM</span>
                        <span><i class="fas fa-umbrella-beach" style="color: var(--primary-color); margin-right: 8px;"></i>Poolside Service</span>
                    </div>
                </div>
            </div>
            
            <!-- Fitness Center -->
            <div style="position: relative; border-radius: 10px; overflow: hidden; height: 500px;">
                <img src="https://images.unsplash.com/photo-1534438327276-14e5300c3a48?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" style="width: 100%; height: 100%; object-fit: cover;">
                <div style="position: absolute; inset: 0; background: linear-gradient(transparent, rgba(0,0,0,0.8)); display: flex; flex-direction: column; justify-content: end; padding: 40px;">
                    <h3 style="color: white; font-size: 32px; margin-bottom: 15px;">Fitness Center</h3>
                    <p style="color: rgba(255,255,255,0.9); font-size: 16px; line-height: 1.7; margin-bottom: 20px;">
                        Stay active in our state-of-the-art fitness center equipped with the latest cardio and strength training equipment. Open 24/7 for your convenience.
                    </p>
                    <div style="display: flex; gap: 30px; color: white;">
                        <span><i class="fas fa-clock" style="color: var(--primary-color); margin-right: 8px;"></i>Open 24 Hours</span>
                        <span><i class="fas fa-user-friends" style="color: var(--primary-color); margin-right: 8px;"></i>Personal Training</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Service Pricing -->
<section style="padding: 80px 0; background-color: var(--gray-light);">
    <div class="container">
        <div style="text-align: center; margin-bottom: 50px;">
            <p style="color: var(--primary-color); font-weight: 600; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 15px;">Services</p>
            <h2 style="font-size: 42px; margin-bottom: 20px;">Spa Menu & Pricing</h2>
        </div>
        
        <div style="background-color: white; border-radius: 10px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.08);">
            <?php if (isset($groupedAmenities['spa'])): ?>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background-color: var(--primary-color); color: white;">
                        <th style="padding: 20px; text-align: left;">Treatment</th>
                        <th style="padding: 20px; text-align: left;">Description</th>
                        <th style="padding: 20px; text-align: center;">Duration</th>
                        <th style="padding: 20px; text-align: right;">Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groupedAmenities['spa'] as $index => $item): ?>
                    <tr style="border-bottom: 1px solid var(--gray-light); <?php echo $index % 2 === 0 ? 'background-color: white;' : 'background-color: var(--gray-light);'; ?>">
                        <td style="padding: 20px; font-weight: 600;"><?php echo htmlspecialchars($item['amenity_name']); ?></td>
                        <td style="padding: 20px; color: #666;"><?php echo htmlspecialchars($item['description'] ?? 'Relaxing treatment'); ?></td>
                        <td style="padding: 20px; text-align: center;"><?php echo $item['duration_minutes'] ?? '60'; ?> min</td>
                        <td style="padding: 20px; text-align: right; font-weight: 600; color: var(--primary-color);"><?php echo formatPrice($item['price']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <p style="font-size: 14px; color: #666; margin-bottom: 20px;">All prices are in Philippine Peso (PHP) and are subject to service charge and taxes</p>
            <a href="contact.php" class="btn btn-primary">Book Your Treatment</a>
        </div>
    </div>
</section>

<!-- Testimonial -->
<section style="padding: 80px 0; background: linear-gradient(rgba(54,125,138,0.9), rgba(40,95,107,0.9)), url('https://images.unsplash.com/photo-1600334129128-685c5582fd35?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') center/cover fixed no-repeat;">
    <div class="container" style="text-align: center; max-width: 800px;">
        <i class="fas fa-quote-left" style="font-size: 50px; color: rgba(255,255,255,0.3); margin-bottom: 20px;"></i>
        <p style="font-size: 24px; color: white; line-height: 1.8; font-style: italic; margin-bottom: 30px;">
            "The spa experience at Bayawan Bai Hotel was absolutely divine. The therapists are highly skilled, and the atmosphere is so peaceful. It's the perfect place to unwind after exploring the beautiful sights of Negros Oriental."
        </p>
        <div style="display: flex; align-items: center; justify-content: center; gap: 15px;">
            <div style="width: 60px; height: 60px; background-color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--primary-color); font-size: 24px; font-weight: 600;">EP</div>
            <div style="text-align: left;">
                <h4 style="color: white; font-size: 18px; margin-bottom: 3px;">Emily Parker</h4>
                <p style="color: rgba(255,255,255,0.8); font-size: 14px;">Manila, Philippines</p>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
