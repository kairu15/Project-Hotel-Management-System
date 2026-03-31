<?php
require_once 'includes/header.php';
$pageTitle = __('Rooms & Accommodations');

// Get all room categories
$db = getDB();
$stmt = $db->query("SELECT * FROM room_categories WHERE status = 'active' ORDER BY base_price");
$rooms = $stmt->fetchAll();

// Get filter values
$filterPrice = $_GET['price'] ?? '';
$filterCapacity = $_GET['capacity'] ?? '';
$filterBed = $_GET['bed'] ?? '';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><?php echo __('Rooms & Suites'); ?></h1>
        <p><?php echo __('Discover our collection of thoughtfully designed accommodations'); ?></p>
    </div>
</div>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <div class="container">
        <ul>
            <li><a href="index.php"><?php echo __('Home'); ?></a></li>
            <li>/</li>
            <li><?php echo __('Rooms & Suites'); ?></li>
        </ul>
    </div>
</div>

<!-- Rooms Section -->
<section class="rooms-page" style="padding: 60px 0; background-color: var(--gray-light);">
    <div class="container">
        <!-- Filters -->
        <div style="background-color: white; padding: 25px; border-radius: 10px; margin-bottom: 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
            <form method="GET" style="display: grid; grid-template-columns: repeat(4, 1fr) auto; gap: 15px; align-items: end;">
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 8px;"><?php echo __('Price Range'); ?></label>
                    <select name="price" style="width: 100%; padding: 12px; border: 1px solid var(--gray-medium); border-radius: 5px; font-size: 14px;">
                        <option value=""><?php echo __('All Prices'); ?></option>
                        <option value="0-3000" <?php echo $filterPrice == '0-3000' ? 'selected' : ''; ?>><?php echo __('Below ₱3,000'); ?></option>
                        <option value="3000-5000" <?php echo $filterPrice == '3000-5000' ? 'selected' : ''; ?>><?php echo __('₱3,000 - ₱5,000'); ?></option>
                        <option value="5000+" <?php echo $filterPrice == '5000+' ? 'selected' : ''; ?>><?php echo __('Above ₱5,000'); ?></option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 8px;"><?php echo __('Guests'); ?></label>
                    <select name="capacity" style="width: 100%; padding: 12px; border: 1px solid var(--gray-medium); border-radius: 5px; font-size: 14px;">
                        <option value=""><?php echo __('Any'); ?></option>
                        <option value="2" <?php echo $filterCapacity == '2' ? 'selected' : ''; ?>><?php echo __('1-2 Guests'); ?></option>
                        <option value="3" <?php echo $filterCapacity == '3' ? 'selected' : ''; ?>><?php echo __('3 Guests'); ?></option>
                        <option value="4" <?php echo $filterCapacity == '4' ? 'selected' : ''; ?>><?php echo __('4+ Guests'); ?></option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 8px;"><?php echo __('Bed Type'); ?></label>
                    <select name="bed" style="width: 100%; padding: 12px; border: 1px solid var(--gray-medium); border-radius: 5px; font-size: 14px;">
                        <option value=""><?php echo __('Any'); ?></option>
                        <option value="Queen" <?php echo $filterBed == 'Queen' ? 'selected' : ''; ?>><?php echo __('Queen Bed'); ?></option>
                        <option value="King" <?php echo $filterBed == 'King' ? 'selected' : ''; ?>><?php echo __('King Bed'); ?></option>
                        <option value="Twin" <?php echo $filterBed == 'Twin' ? 'selected' : ''; ?>><?php echo __('Twin Beds'); ?></option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 8px;"><?php echo __('Sort By'); ?></label>
                    <select name="sort" style="width: 100%; padding: 12px; border: 1px solid var(--gray-medium); border-radius: 5px; font-size: 14px;">
                        <option value="price_asc"><?php echo __('Price: Low to High'); ?></option>
                        <option value="price_desc"><?php echo __('Price: High to Low'); ?></option>
                        <option value="capacity"><?php echo __('Capacity'); ?></option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-filter"></i> <?php echo __('Filter'); ?>
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Room Grid -->
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px;">
            <?php foreach ($rooms as $index => $room): 
                // Apply filters
                $show = true;
                if ($filterPrice) {
                    if ($filterPrice == '0-3000' && $room['base_price'] >= 3000) $show = false;
                    if ($filterPrice == '3000-5000' && ($room['base_price'] < 3000 || $room['base_price'] > 5000)) $show = false;
                    if ($filterPrice == '5000+' && $room['base_price'] <= 5000) $show = false;
                }
                if ($filterCapacity && $room['max_occupancy'] < $filterCapacity) $show = false;
                if ($filterBed && strpos($room['bed_type'], $filterBed) === false) $show = false;
                
                if ($show):
                    $amenities = explode(', ', $room['amenities']);
                    // Get primary image or use default
                    $primaryImage = $room['image_primary'] ?? '';
                    if ($primaryImage) {
                        // Add assets/ prefix if not already there
                        if (strpos($primaryImage, 'http') !== 0 && strpos($primaryImage, 'assets/') !== 0) {
                            $primaryImage = 'assets/' . $primaryImage;
                        }
                    } else {
                        // Default placeholder images
                        $defaultImages = ['1631049307260-da0c0f11336a','1566666208517-13f42e1e3c2c','1590490360182-c33d57733427','1582719478250-c89cae141e86'];
                        $primaryImage = 'https://images.unsplash.com/photo-' . $defaultImages[$index % 4] . '?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80';
                    }
            ?>
            <div style="background-color: white; border-radius: 10px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.08); display: grid; grid-template-columns: 1fr 1fr;">
                <!-- Image -->
                <div style="position: relative; overflow: hidden;">
                    <img src="<?php echo htmlspecialchars($primaryImage); ?>" alt="<?php echo htmlspecialchars($room['category_name']); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                    <div style="position: absolute; top: 15px; left: 15px; background-color: var(--primary-color); color: white; padding: 8px 15px; border-radius: 5px; font-size: 14px; font-weight: 600;">
                        <?php echo __('From'); ?> <?php echo formatPrice($room['base_price']); ?>
                    </div>
                </div>
                
                <!-- Content -->
                <div style="padding: 30px; display: flex; flex-direction: column;">
                    <h3 style="font-size: 24px; margin-bottom: 10px;"><?php echo htmlspecialchars($room['category_name']); ?></h3>
                    
                    <!-- Room Specs -->
                    <div style="display: flex; gap: 20px; margin-bottom: 15px; font-size: 14px; color: #666;">
                        <span><i class="fas fa-user" style="color: var(--primary-color); margin-right: 5px;"></i> <?php echo $room['max_occupancy']; ?> <?php echo __('Guests'); ?></span>
                        <span><i class="fas fa-vector-square" style="color: var(--primary-color); margin-right: 5px;"></i> <?php echo $room['room_size_sqm']; ?> m²</span>
                        <span><i class="fas fa-bed" style="color: var(--primary-color); margin-right: 5px;"></i> <?php echo htmlspecialchars($room['bed_type']); ?></span>
                    </div>
                    
                    <p style="font-size: 15px; color: #666; line-height: 1.7; margin-bottom: 20px; flex-grow: 1;">
                        <?php echo htmlspecialchars($room['description']); ?>
                    </p>
                    
                    <!-- Amenities Icons -->
                    <div style="display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;">
                        <?php foreach (array_slice($amenities, 0, 4) as $amenity): ?>
                        <span style="font-size: 13px; color: #666; background-color: var(--gray-light); padding: 5px 12px; border-radius: 20px;">
                            <i class="fas fa-check" style="color: var(--success-color); margin-right: 5px;"></i>
                            <?php echo htmlspecialchars(trim($amenity)); ?>
                        </span>
                        <?php endforeach; ?>
                        <?php if (count($amenities) > 4): ?>
                        <span style="font-size: 13px; color: var(--primary-color);">+<?php echo count($amenities) - 4; ?> <?php echo __('more'); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Actions -->
                    <div style="display: flex; gap: 10px;">
                        <a href="room-details.php?id=<?php echo $room['category_id']; ?>" class="btn btn-outline" style="flex: 1; text-align: center;"><?php echo __('View Details'); ?></a>
                        <a href="booking.php?room=<?php echo $room['category_id']; ?>" class="btn btn-primary" style="flex: 1; text-align: center;"><?php echo __('Book Now'); ?></a>
                    </div>
                </div>
            </div>
            <?php endif; endforeach; ?>
        </div>
        
        <!-- Compare Section -->
        <div style="margin-top: 60px; text-align: center;">
            <h3 style="font-size: 28px; margin-bottom: 30px;"><?php echo __('Compare Room Types'); ?></h3>
            <div style="background-color: white; border-radius: 10px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.08);">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: var(--primary-color); color: white;">
                            <th style="padding: 20px; text-align: left; font-weight: 600;"><?php echo __('Feature'); ?></th>
                            <?php foreach ($rooms as $room): ?>
                            <th style="padding: 20px; text-align: center; font-weight: 600;"><?php echo htmlspecialchars($room['category_name']); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom: 1px solid var(--gray-light);">
                            <td style="padding: 15px 20px; font-weight: 500;"><?php echo __('Max Occupancy'); ?></td>
                            <?php foreach ($rooms as $room): ?>
                            <td style="padding: 15px 20px; text-align: center; color: #666;"><?php echo $room['max_occupancy']; ?> <?php echo __('Guests'); ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--gray-light);">
                            <td style="padding: 15px 20px; font-weight: 500;"><?php echo __('Room Size'); ?></td>
                            <?php foreach ($rooms as $room): ?>
                            <td style="padding: 15px 20px; text-align: center; color: #666;"><?php echo $room['room_size_sqm']; ?> m²</td>
                            <?php endforeach; ?>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--gray-light);">
                            <td style="padding: 15px 20px; font-weight: 500;"><?php echo __('Bed Type'); ?></td>
                            <?php foreach ($rooms as $room): ?>
                            <td style="padding: 15px 20px; text-align: center; color: #666;"><?php echo htmlspecialchars($room['bed_type']); ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--gray-light);">
                            <td style="padding: 15px 20px; font-weight: 500;"><?php echo __('Starting Price'); ?></td>
                            <?php foreach ($rooms as $room): ?>
                            <td style="padding: 15px 20px; text-align: center; color: var(--primary-color); font-weight: 600;"><?php echo formatPrice($room['base_price']); ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td style="padding: 15px 20px; font-weight: 500;"><?php echo __('Action'); ?></td>
                            <?php foreach ($rooms as $room): ?>
                            <td style="padding: 15px 20px; text-align: center;">
                                <a href="booking.php?room=<?php echo $room['category_id']; ?>" class="btn btn-primary btn-sm"><?php echo __('Book'); ?></a>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<style>
/* Rooms Page Responsive */
@media (max-width: 992px) {
    .rooms-page .container > div:first-child form {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
    
    .rooms-page .container > div:first-child form > div:last-child {
        grid-column: span 2;
    }
    
    .rooms-page .container > div:last-child {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .rooms-page table {
        font-size: 14px;
    }
    
    .rooms-page table th,
    .rooms-page table td {
        padding: 10px 15px;
    }
}

@media (max-width: 768px) {
    .rooms-page .container > div:first-child {
        padding: 20px;
        margin-bottom: 30px;
    }
    
    .rooms-page .container > div:first-child form {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .rooms-page .container > div:first-child form > div:last-child {
        grid-column: span 1;
    }
    
    .rooms-page .container > div:first-child form label {
        font-size: 12px;
    }
    
    .rooms-page .container > div:first-child form select,
    .rooms-page .container > div:first-child form button {
        padding: 10px;
        font-size: 13px;
    }
    
    .rooms-page .container > div:last-child {
        overflow-x: auto;
    }
    
    .rooms-page table {
        min-width: 700px;
        font-size: 13px;
    }
    
    .rooms-page table th,
    .rooms-page table td {
        padding: 8px 12px;
    }
    
    .rooms-page table img {
        width: 80px;
        height: 60px;
    }
}

@media (max-width: 576px) {
    .rooms-page .container > div:first-child {
        padding: 15px;
        margin-bottom: 20px;
    }
    
    .rooms-page .container > div:first-child form label {
        font-size: 11px;
        margin-bottom: 6px;
    }
    
    .rooms-page .container > div:first-child form select,
    .rooms-page .container > div:first-child form button {
        padding: 8px;
        font-size: 12px;
    }
    
    .rooms-page table {
        min-width: 600px;
        font-size: 12px;
    }
    
    .rooms-page table th,
    .rooms-page table td {
        padding: 6px 10px;
    }
    
    .rooms-page table img {
        width: 60px;
        height: 45px;
    }
    
    .rooms-page .btn-sm {
        padding: 6px 12px;
        font-size: 11px;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
