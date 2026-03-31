<?php
require_once 'includes/header.php';
$pageTitle = __('Dining & Restaurants');

// Get menu categories and items
$db = getDB();
$categoriesStmt = $db->query("SELECT * FROM menu_categories ORDER BY sort_order");
$categories = $categoriesStmt->fetchAll();

// Get all menu items
$itemsStmt = $db->query("SELECT mi.*, mc.category_name FROM menu_items mi JOIN menu_categories mc ON mi.cat_id = mc.cat_id WHERE mi.is_available = 1 ORDER BY mc.sort_order, mi.item_name");
$menuItems = $itemsStmt->fetchAll();

// Group items by category
$groupedItems = [];
foreach ($menuItems as $item) {
    $groupedItems[$item['category_name']][] = $item;
}
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><?php echo __('Dining & Restaurants'); ?></h1>
        <p><?php echo __('Savor the authentic flavors of Negros Oriental'); ?></p>
    </div>
</div>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <div class="container">
        <ul>
            <li><a href="index.php"><?php echo __('Home'); ?></a></li>
            <li>/</li>
            <li><?php echo __('Dining'); ?></li>
        </ul>
    </div>
</div>

<!-- Hero Section -->
<section style="padding: 0; position: relative; height: 500px; background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') center/cover no-repeat;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; color: white; max-width: 800px; padding: 0 20px;">
        <p style="font-size: 18px; letter-spacing: 3px; text-transform: uppercase; margin-bottom: 20px;"><?php echo __('Culinary Excellence'); ?></p>
        <h2 style="font-size: 48px; color: white; margin-bottom: 25px;"><?php echo __('A Feast for the Senses'); ?></h2>
        <p style="font-size: 18px; line-height: 1.8; opacity: 0.9;"><?php echo __('Experience the rich culinary heritage of Negros Oriental through our expertly crafted dishes, prepared with the freshest local ingredients'); ?></p>
    </div>
</section>

<!-- Restaurant Info -->
<section style="padding: 80px 0; background-color: var(--light-color);">
    <div class="container">
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px;">
            <div style="text-align: center; padding: 40px; background-color: var(--gray-light); border-radius: 10px;">
                <i class="fas fa-utensils" style="font-size: 50px; color: var(--primary-color); margin-bottom: 20px;"></i>
                <h3 style="font-size: 24px; margin-bottom: 15px;"><?php echo __('Bai Restaurant'); ?></h3>
                <p style="color: #666; line-height: 1.7; margin-bottom: 20px;"><?php echo __('Our signature restaurant serving authentic Filipino and international cuisine with a modern twist'); ?></p>
                <p style="font-size: 14px; color: var(--primary-color);"><i class="fas fa-clock"></i> <?php echo __('Open 6:00 AM - 10:00 PM'); ?></p>
            </div>
            <div style="text-align: center; padding: 40px; background-color: var(--gray-light); border-radius: 10px;">
                <i class="fas fa-coffee" style="font-size: 50px; color: var(--primary-color); margin-bottom: 20px;"></i>
                <h3 style="font-size: 24px; margin-bottom: 15px;"><?php echo __('Café Bai'); ?></h3>
                <p style="color: #666; line-height: 1.7; margin-bottom: 20px;"><?php echo __('Relax with premium coffee, fresh pastries, and light bites in our cozy café setting'); ?></p>
                <p style="font-size: 14px; color: var(--primary-color);"><i class="fas fa-clock"></i> <?php echo __('Open 7:00 AM - 9:00 PM'); ?></p>
            </div>
            <div style="text-align: center; padding: 40px; background-color: var(--gray-light); border-radius: 10px;">
                <i class="fas fa-glass-martini-alt" style="font-size: 50px; color: var(--primary-color); margin-bottom: 20px;"></i>
                <h3 style="font-size: 24px; margin-bottom: 15px;"><?php echo __('Rooftop Lounge'); ?></h3>
                <p style="color: #666; line-height: 1.7; margin-bottom: 20px;"><?php echo __('Enjoy handcrafted cocktails and breathtaking bay views at our exclusive rooftop bar'); ?></p>
                <p style="font-size: 14px; color: var(--primary-color);"><i class="fas fa-clock"></i> <?php echo __('Open 5:00 PM - 12:00 AM'); ?></p>
            </div>
        </div>
    </div>
</section>

<!-- Menu Section -->
<section style="padding: 80px 0; background-color: var(--gray-light);">
    <div class="container">
        <div style="text-align: center; margin-bottom: 50px;">
            <p style="color: var(--primary-color); font-weight: 600; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 15px;"><?php echo __('Our Menu'); ?></p>
            <h2 style="font-size: 42px; margin-bottom: 20px;"><?php echo __('Culinary Delights'); ?></h2>
        </div>
        
        <!-- Menu Categories Tabs -->
        <div style="display: flex; justify-content: center; gap: 15px; margin-bottom: 40px; flex-wrap: wrap;">
            <button class="menu-tab active" onclick="filterMenu('all', this)" style="padding: 12px 30px; border: 2px solid var(--primary-color); background-color: var(--primary-color); color: white; border-radius: 30px; cursor: pointer; font-weight: 600; transition: all 0.3s;"><?php echo __('All'); ?></button>
            <?php foreach ($categories as $category): ?>
            <button class="menu-tab" onclick="filterMenu('<?php echo htmlspecialchars($category['category_name']); ?>', this)" style="padding: 12px 30px; border: 2px solid var(--primary-color); background-color: transparent; color: var(--primary-color); border-radius: 30px; cursor: pointer; font-weight: 600; transition: all 0.3s;">
                <?php echo htmlspecialchars($category['category_name']); ?>
            </button>
            <?php endforeach; ?>
        </div>
        
        <!-- Menu Items Grid -->
        <div id="menuGrid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px;">
            <?php foreach ($menuItems as $item): 
                // Get item image or use default
                $itemImage = $item['image'] ?? '';
                if ($itemImage) {
                    // Add assets/ prefix if not already there
                    if (strpos($itemImage, 'http') !== 0 && strpos($itemImage, 'assets/') !== 0) {
                        $itemImage = 'assets/' . $itemImage;
                    }
                } else {
                    // Default placeholder images
                    $defaultImages = ['1504674900247-0877df9cc836','1540189549336-e6e99c3679fe','1565299624946-b28f40a0ae38','1567620905732-2d1ec7ab7445'];
                    $itemImage = 'https://images.unsplash.com/photo-' . $defaultImages[$item['item_id'] % 4] . '?ixlib=rb-4.0.3&auto=format&fit=crop&w=300&q=80';
                }
            ?>
            <div class="menu-item" data-category="<?php echo htmlspecialchars($item['category_name']); ?>" style="background-color: white; border-radius: 10px; overflow: hidden; display: flex; box-shadow: 0 5px 20px rgba(0,0,0,0.08); transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="width: 150px; min-height: 150px;">
                    <img src="<?php echo htmlspecialchars($itemImage); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <div style="flex: 1; padding: 25px; display: flex; flex-direction: column;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                        <div>
                            <h4 style="font-size: 20px; margin-bottom: 5px;"><?php echo htmlspecialchars($item['item_name']); ?></h4>
                            <?php if ($item['dietary_info']): ?>
                            <span style="font-size: 12px; color: var(--success-color);"><i class="fas fa-leaf"></i> <?php echo htmlspecialchars($item['dietary_info']); ?></span>
                            <?php endif; ?>
                        </div>
                        <span style="font-size: 20px; font-weight: 700; color: var(--primary-color);"><?php echo formatPrice($item['price']); ?></span>
                    </div>
                    <p style="font-size: 14px; color: #666; line-height: 1.6; flex-grow: 1;"><?php echo htmlspecialchars($item['description']); ?></p>
                    <div style="display: flex; gap: 10px; margin-top: 15px;">
                        <a href="foods-details.php?id=<?php echo $item['item_id']; ?>" class="btn btn-outline" style="flex: 1; padding: 10px; font-size: 14px;">
                            <i class="fas fa-info-circle"></i> Details
                        </a>
                        <a href="order-now.php?item_id=<?php echo $item['item_id']; ?>" class="btn btn-primary" style="flex: 1; padding: 10px; font-size: 14px;">
                            <i class="fas fa-shopping-cart"></i> Order
                        </a>
                    </div>
                    <?php if ($item['is_special']): ?>
                    <div style="margin-top: 10px;">
                        <span style="background-color: var(--warning-color); color: var(--dark-color); padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                            <i class="fas fa-star"></i> Chef's Special
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Special Dining Experiences -->
<section style="padding: 80px 0; background-color: var(--light-color);">
    <div class="container">
        <div style="text-align: center; margin-bottom: 50px;">
            <p style="color: var(--primary-color); font-weight: 600; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 15px;">Experiences</p>
            <h2 style="font-size: 42px; margin-bottom: 20px;">Special Dining</h2>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px;">
            <div style="position: relative; border-radius: 10px; overflow: hidden; height: 400px;">
                <img src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" style="width: 100%; height: 100%; object-fit: cover;">
                <div style="position: absolute; inset: 0; background: linear-gradient(transparent, rgba(0,0,0,0.8)); display: flex; flex-direction: column; justify-content: end; padding: 30px;">
                    <h3 style="color: white; font-size: 24px; margin-bottom: 10px;">Private Dining</h3>
                    <p style="color: rgba(255,255,255,0.8); font-size: 14px; margin-bottom: 20px;">Exclusive dining experience for special occasions with personalized service.</p>
                    <a href="events.php" class="btn btn-primary" style="align-self: start;">Inquire Now</a>
                </div>
            </div>
            <div style="position: relative; border-radius: 10px; overflow: hidden; height: 400px;">
                <img src="https://images.unsplash.com/photo-1559339352-11d035aa65de?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" style="width: 100%; height: 100%; object-fit: cover;">
                <div style="position: absolute; inset: 0; background: linear-gradient(transparent, rgba(0,0,0,0.8)); display: flex; flex-direction: column; justify-content: end; padding: 30px;">
                    <h3 style="color: white; font-size: 24px; margin-bottom: 10px;">Breakfast Buffet</h3>
                    <p style="color: rgba(255,255,255,0.8); font-size: 14px; margin-bottom: 20px;">Start your day with our extensive buffet featuring local and international favorites.</p>
                    <span style="color: var(--primary-color); font-weight: 600;"><i class="fas fa-clock"></i> 6:00 AM - 10:00 AM</span>
                </div>
            </div>
            <div style="position: relative; border-radius: 10px; overflow: hidden; height: 400px;">
                <img src="https://images.unsplash.com/photo-1550966871-3ed3cdb5ed0c?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" style="width: 100%; height: 100%; object-fit: cover;">
                <div style="position: absolute; inset: 0; background: linear-gradient(transparent, rgba(0,0,0,0.8)); display: flex; flex-direction: column; justify-content: end; padding: 30px;">
                    <h3 style="color: white; font-size: 24px; margin-bottom: 10px;">Romantic Dinner</h3>
                    <p style="color: rgba(255,255,255,0.8); font-size: 14px; margin-bottom: 20px;">Candlelit dinner by the bay with a specially curated menu for two.</p>
                    <a href="booking.php" class="btn btn-outline" style="border-color: white; color: white; align-self: start;">Reserve</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section style="padding: 80px 0; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));">
    <div class="container" style="text-align: center;">
        <h2 style="color: white; font-size: 36px; margin-bottom: 15px;">Ready to Dine With Us?</h2>
        <p style="color: rgba(255,255,255,0.9); font-size: 18px; margin-bottom: 30px;">Reserve your table now and experience the best of Negros Oriental cuisine</p>
        <a href="contact.php" class="btn" style="background-color: white; color: var(--primary-color); padding: 15px 40px; font-size: 16px; font-weight: 600;">Make a Reservation</a>
    </div>
</section>

<script>
function filterMenu(category, button) {
    // Update button styles
    document.querySelectorAll('.menu-tab').forEach(btn => {
        btn.style.backgroundColor = 'transparent';
        btn.style.color = 'var(--primary-color)';
    });
    button.style.backgroundColor = 'var(--primary-color)';
    button.style.color = 'white';
    
    // Filter items
    const items = document.querySelectorAll('.menu-item');
    items.forEach(item => {
        if (category === 'all' || item.dataset.category === category) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
