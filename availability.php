<?php
$pageTitle = 'Check Availability';
require_once 'includes/header.php';

// Get search parameters
$checkIn = $_GET['check_in'] ?? '';
$checkOut = $_GET['check_out'] ?? '';
$guests = (int)($_GET['guests'] ?? 2);
$roomType = $_GET['room_type'] ?? '';

$availableRooms = [];
$categoriesWithAvailability = [];

// Perform search if dates are provided
if ($checkIn && $checkOut) {
    if (strtotime($checkOut) <= strtotime($checkIn)) {
        showAlert('Check-out date must be after check-in date', 'danger');
    } else {
        $nights = calculateNights($checkIn, $checkOut);
        if ($nights > 0) {
            if ($roomType) {
                // Search specific room type
                $availableRooms = checkAvailability($checkIn, $checkOut, $roomType);
            }
            // Get all categories with availability
            $categoriesWithAvailability = getCategoriesWithAvailability($checkIn, $checkOut);
        }
    }
}

$db = getDB();
$allCategories = $db->query("SELECT * FROM room_categories WHERE status = 'active' ORDER BY base_price")->fetchAll();
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>Check Availability</h1>
        <p>Find the perfect room for your stay</p>
    </div>
</div>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <div class="container">
        <ul>
            <li><a href="index.php">Home</a></li>
            <li>/</li>
            <li>Check Availability</li>
        </ul>
    </div>
</div>

<!-- Search Section -->
<section style="padding: 40px 0; background-color: var(--primary-color);">
    <div class="container">
        <form action="" method="GET" style="display: grid; grid-template-columns: repeat(4, 1fr) auto; gap: 20px; align-items: end;">
            <div>
                <label style="display: block; color: white; margin-bottom: 8px; font-size: 14px;">Check-in Date</label>
                <input type="date" name="check_in" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($checkIn); ?>"
                    style="width: 100%; padding: 15px; border: none; border-radius: 5px; font-size: 15px;">
            </div>
            <div>
                <label style="display: block; color: white; margin-bottom: 8px; font-size: 14px;">Check-out Date</label>
                <input type="date" name="check_out" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" value="<?php echo htmlspecialchars($checkOut); ?>"
                    style="width: 100%; padding: 15px; border: none; border-radius: 5px; font-size: 15px;">
            </div>
            <div>
                <label style="display: block; color: white; margin-bottom: 8px; font-size: 14px;">Guests</label>
                <select name="guests" style="width: 100%; padding: 15px; border: none; border-radius: 5px; font-size: 15px;">
                    <?php for ($i = 1; $i <= 6; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo $guests == $i ? 'selected' : ''; ?>><?php echo $i; ?> Guest<?php echo $i > 1 ? 's' : ''; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label style="display: block; color: white; margin-bottom: 8px; font-size: 14px;">Room Type</label>
                <select name="room_type" style="width: 100%; padding: 15px; border: none; border-radius: 5px; font-size: 15px;">
                    <option value="">All Room Types</option>
                    <?php foreach ($allCategories as $cat): ?>
                    <option value="<?php echo $cat['category_id']; ?>" <?php echo $roomType == $cat['category_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['category_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-dark" style="width: 100%; padding: 15px;">
                    <i class="fas fa-search"></i> Search
                </button>
            </div>
        </form>
    </div>
</section>

<!-- Results Section -->
<section style="padding: 60px 0; background-color: var(--gray-light); min-height: 500px;">
    <div class="container">
        <?php if ($checkIn && $checkOut): ?>
            <?php if (strtotime($checkOut) > strtotime($checkIn)): ?>
                <?php
                $nights = calculateNights($checkIn, $checkOut);
                $hasResults = count($categoriesWithAvailability) > 0;
                ?>
                
                <div style="text-align: center; margin-bottom: 40px;">
                    <h2 style="font-size: 28px; margin-bottom: 10px;">Available Rooms</h2>
                    <p style="color: #666;">
                        <?php echo formatDate($checkIn); ?> - <?php echo formatDate($checkOut); ?> 
                        <span style="margin: 0 10px;">|</span> 
                        <?php echo $nights; ?> night<?php echo $nights > 1 ? 's' : ''; ?> 
                        <span style="margin: 0 10px;">|</span> 
                        <?php echo $guests; ?> guest<?php echo $guests > 1 ? 's' : ''; ?>
                    </p>
                </div>
                
                <?php if ($hasResults): ?>
                <div style="display: grid; grid-template-columns: 1fr; gap: 30px;">
                    <?php foreach ($categoriesWithAvailability as $category): 
                        if ($category['available_count'] > 0):
                            // Check if this category can accommodate the guests
                            if ($category['max_occupancy'] >= $guests):
                                $totalPrice = $category['base_price'] * $nights;
                                $amenities = explode(', ', $category['amenities']);
                    ?>
                    <div style="background-color: white; border-radius: 10px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.08); display: grid; grid-template-columns: 350px 1fr;">
                        <!-- Image -->
                        <div style="position: relative; height: 100%; min-height: 300px;">
                            <img src="https://images.unsplash.com/photo-1631049307260-da0c0f11336a?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" 
                                style="width: 100%; height: 100%; object-fit: cover;">
                            <div style="position: absolute; top: 20px; left: 20px; background-color: var(--success-color); color: white; padding: 8px 15px; border-radius: 5px; font-size: 14px; font-weight: 600;">
                                <?php echo $category['available_count']; ?> room<?php echo $category['available_count'] > 1 ? 's' : ''; ?> available
                            </div>
                        </div>
                        
                        <!-- Content -->
                        <div style="padding: 35px; display: flex; flex-direction: column;">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                                <div>
                                    <h3 style="font-size: 26px; margin-bottom: 5px;"><?php echo htmlspecialchars($category['category_name']); ?></h3>
                                    <p style="color: #666; font-size: 15px;"><?php echo htmlspecialchars($category['bed_type']); ?> • <?php echo $category['room_size_sqm']; ?> m²</p>
                                </div>
                                <div style="text-align: right;">
                                    <p style="font-size: 28px; font-weight: 700; color: var(--primary-color); margin: 0;"><?php echo formatPrice($category['base_price']); ?></p>
                                    <p style="font-size: 13px; color: #666;">per night</p>
                                </div>
                            </div>
                            
                            <p style="color: #666; line-height: 1.7; margin-bottom: 20px; flex-grow: 1;">
                                <?php echo htmlspecialchars($category['description']); ?>
                            </p>
                            
                            <!-- Amenities -->
                            <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 25px;">
                                <?php foreach (array_slice($amenities, 0, 5) as $amenity): ?>
                                <span style="background-color: var(--gray-light); padding: 8px 15px; border-radius: 20px; font-size: 13px; color: #666;">
                                    <i class="fas fa-check" style="color: var(--success-color); margin-right: 5px;"></i>
                                    <?php echo htmlspecialchars(trim($amenity)); ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Price Summary -->
                            <div style="background-color: var(--gray-light); padding: 20px; border-radius: 8px; margin-bottom: 25px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                    <span style="color: #666;"><?php echo formatPrice($category['base_price']); ?> x <?php echo $nights; ?> nights</span>
                                    <span style="font-weight: 600;"><?php echo formatPrice($totalPrice); ?></span>
                                </div>
                                <div style="display: flex; justify-content: space-between; padding-top: 15px; border-top: 1px solid #ddd;">
                                    <span style="font-weight: 600;">Total</span>
                                    <span style="font-size: 22px; font-weight: 700; color: var(--primary-color);"><?php echo formatPrice($totalPrice); ?></span>
                                </div>
                            </div>
                            
                            <!-- Actions -->
                            <div style="display: flex; gap: 15px;">
                                <a href="room-details.php?id=<?php echo $category['category_id']; ?>" class="btn btn-outline" style="flex: 1; text-align: center;">View Details</a>
                                <a href="booking.php?room=<?php echo $category['category_id']; ?>&check_in=<?php echo $checkIn; ?>&check_out=<?php echo $checkOut; ?>" class="btn btn-primary" style="flex: 1; text-align: center;">Book Now</a>
                            </div>
                        </div>
                    </div>
                    <?php 
                            endif;
                        endif;
                    endforeach; 
                    ?>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 80px 20px; background-color: white; border-radius: 10px;">
                    <i class="fas fa-calendar-times" style="font-size: 80px; color: var(--gray-medium); margin-bottom: 30px;"></i>
                    <h3 style="font-size: 28px; margin-bottom: 15px;">No Availability</h3>
                    <p style="color: #666; font-size: 16px; margin-bottom: 30px; max-width: 500px; margin-left: auto; margin-right: auto;">
                        Sorry, we don't have any rooms available for your selected dates. Please try different dates or contact our reservations team for assistance.
                    </p>
                    <div style="display: flex; gap: 15px; justify-content: center;">
                        <a href="contact.php" class="btn btn-outline">Contact Reservations</a>
                        <button onclick="window.scrollTo({top: 0, behavior: 'smooth'});" class="btn btn-primary">Modify Search</button>
                    </div>
                </div>
                <?php endif; ?>
                
            <?php endif; ?>
        <?php else: ?>
        <div style="text-align: center; padding: 100px 20px;">
            <i class="fas fa-search" style="font-size: 80px; color: var(--gray-medium); margin-bottom: 30px;"></i>
            <h3 style="font-size: 28px; margin-bottom: 15px;">Search for Available Rooms</h3>
            <p style="color: #666; font-size: 16px; max-width: 500px; margin: 0 auto;">
                Enter your check-in and check-out dates above to see available rooms and rates for your stay.
            </p>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
// Date validation
document.querySelector('input[name="check_in"]').addEventListener('change', function() {
    const checkOut = document.querySelector('input[name="check_out"]');
    checkOut.min = this.value;
    if (checkOut.value && checkOut.value <= this.value) {
        const nextDay = new Date(this.value);
        nextDay.setDate(nextDay.getDate() + 1);
        checkOut.value = nextDay.toISOString().split('T')[0];
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
