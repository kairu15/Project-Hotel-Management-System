<?php
$pageTitle = 'Room Details';
require_once 'includes/config.php';

// Get room category ID from URL
$categoryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$categoryId) {
    redirect('rooms.php');
}

// Get room details
$db = getDB();
$stmt = $db->prepare("SELECT * FROM room_categories WHERE category_id = ? AND status = 'active'");
$stmt->execute([$categoryId]);
$room = $stmt->fetch();

if (!$room) {
    showAlert('Room not found', 'danger');
    redirect('rooms.php');
}

require_once 'includes/header.php';

// Get available rooms count
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$availableRooms = checkAvailability($today, $tomorrow, $categoryId);

// Get similar rooms
$similarStmt = $db->prepare("SELECT * FROM room_categories WHERE category_id != ? AND status = 'active' ORDER BY base_price LIMIT 3");
$similarStmt->execute([$categoryId]);
$similarRooms = $similarStmt->fetchAll();

$amenities = explode(', ', $room['amenities']);
?>

<!-- Page Header -->
<div style="height: 500px; position: relative; background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('https://images.unsplash.com/photo-1631049307260-da0c0f11336a?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') center/cover no-repeat;">
    <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(transparent, rgba(0,0,0,0.8)); padding: 60px 0;">
        <div class="container">
            <h1 style="color: white; font-size: 48px; margin-bottom: 10px;"><?php echo htmlspecialchars($room['category_name']); ?></h1>
            <p style="color: rgba(255,255,255,0.9); font-size: 18px;">Experience luxury and comfort</p>
        </div>
    </div>
</div>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <div class="container">
        <ul>
            <li><a href="index.php">Home</a></li>
            <li>/</li>
            <li><a href="rooms.php">Rooms</a></li>
            <li>/</li>
            <li><?php echo htmlspecialchars($room['category_name']); ?></li>
        </ul>
    </div>
</div>

<!-- Room Details Section -->
<section style="padding: 60px 0; background-color: var(--gray-light);">
    <div class="container">
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 40px;">
            <!-- Main Content -->
            <div>
                <!-- Image Gallery -->
                <div style="background-color: white; padding: 20px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.08);">
                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px;">
                        <div style="border-radius: 10px; overflow: hidden; height: 400px;">
                            <img id="mainImage" src="https://images.unsplash.com/photo-1631049307260-da0c0f11336a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div style="display: grid; grid-template-rows: repeat(3, 1fr); gap: 10px;">
                            <div style="border-radius: 10px; overflow: hidden; cursor: pointer;" onclick="changeImage('https://images.unsplash.com/photo-1566666208517-13f42e1e3c2c?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80')">
                                <img src="https://images.unsplash.com/photo-1566666208517-13f42e1e3c2c?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                            </div>
                            <div style="border-radius: 10px; overflow: hidden; cursor: pointer;" onclick="changeImage('https://images.unsplash.com/photo-1582719478250-c89cae141e86?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80')">
                                <img src="https://images.unsplash.com/photo-1582719478250-c89cae141e86?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                            </div>
                            <div style="border-radius: 10px; overflow: hidden; cursor: pointer; position: relative;" onclick="changeImage('https://images.unsplash.com/photo-1590490360182-c33d57733427?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80')">
                                <img src="https://images.unsplash.com/photo-1590490360182-c33d57733427?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                                <div style="position: absolute; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">
                                    + More Photos
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Description -->
                <div style="background-color: white; padding: 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.08);">
                    <h2 style="font-size: 28px; margin-bottom: 20px;">About This Room</h2>
                    <p style="font-size: 16px; line-height: 1.8; color: #666;">
                        <?php echo nl2br(htmlspecialchars($room['description'])); ?>
                    </p>
                    <p style="font-size: 16px; line-height: 1.8; color: #666; margin-top: 15px;">
                        Our <?php echo htmlspecialchars($room['category_name']); ?> offers the perfect blend of comfort and elegance. 
                        With <?php echo $room['room_size_sqm']; ?> square meters of space, it comfortably accommodates up to 
                        <?php echo $room['max_occupancy']; ?> guests. The room features a luxurious 
                        <?php echo htmlspecialchars($room['bed_type']); ?> and stunning views of Bayawan City.
                    </p>
                </div>
                
                <!-- Amenities -->
                <div style="background-color: white; padding: 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.08);">
                    <h2 style="font-size: 28px; margin-bottom: 25px;">Room Amenities</h2>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                        <?php foreach ($amenities as $amenity): ?>
                        <div style="display: flex; align-items: center; gap: 10px; padding: 12px; background-color: var(--gray-light); border-radius: 5px;">
                            <i class="fas fa-check-circle" style="color: var(--success-color);"></i>
                            <span style="font-size: 14px;"><?php echo htmlspecialchars(trim($amenity)); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Room Policy -->
                <div style="background-color: white; padding: 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.08);">
                    <h2 style="font-size: 28px; margin-bottom: 20px;">Room Policies</h2>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div style="padding: 20px; background-color: var(--gray-light); border-radius: 5px;">
                            <i class="fas fa-clock" style="color: var(--primary-color); font-size: 24px; margin-bottom: 10px;"></i>
                            <h4 style="font-size: 16px; margin-bottom: 5px;">Check-in/Check-out</h4>
                            <p style="font-size: 14px; color: #666;">Check-in: 2:00 PM<br>Check-out: 12:00 PM</p>
                        </div>
                        <div style="padding: 20px; background-color: var(--gray-light); border-radius: 5px;">
                            <i class="fas fa-ban" style="color: var(--primary-color); font-size: 24px; margin-bottom: 10px;"></i>
                            <h4 style="font-size: 16px; margin-bottom: 5px;">Cancellation</h4>
                            <p style="font-size: 14px; color: #666;">Free cancellation up to 48 hours before check-in</p>
                        </div>
                        <div style="padding: 20px; background-color: var(--gray-light); border-radius: 5px;">
                            <i class="fas fa-paw" style="color: var(--primary-color); font-size: 24px; margin-bottom: 10px;"></i>
                            <h4 style="font-size: 16px; margin-bottom: 5px;">Pets</h4>
                            <p style="font-size: 14px; color: #666;">No pets allowed (service animals excepted)</p>
                        </div>
                        <div style="padding: 20px; background-color: var(--gray-light); border-radius: 5px;">
                            <i class="fas fa-smoking-ban" style="color: var(--primary-color); font-size: 24px; margin-bottom: 10px;"></i>
                            <h4 style="font-size: 16px; margin-bottom: 5px;">Smoking</h4>
                            <p style="font-size: 14px; color: #666;">Non-smoking room</p>
                        </div>
                    </div>
                </div>
                
                <!-- Reviews Section -->
                <div style="background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.08);">
                    <h2 style="font-size: 28px; margin-bottom: 25px;">Guest Reviews</h2>
                    <div style="display: flex; align-items: center; gap: 30px; margin-bottom: 30px; padding: 25px; background-color: var(--gray-light); border-radius: 10px;">
                        <div style="text-align: center;">
                            <div style="font-size: 48px; font-weight: 700; color: var(--primary-color);">4.8</div>
                            <div style="color: var(--warning-color); font-size: 18px;">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star-half-alt"></i>
                            </div>
                            <div style="font-size: 14px; color: #666; margin-top: 5px;">Based on 127 reviews</div>
                        </div>
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                <span style="font-size: 14px; width: 60px;">5 stars</span>
                                <div style="flex: 1; height: 8px; background-color: #ddd; border-radius: 4px; overflow: hidden;">
                                    <div style="width: 85%; height: 100%; background-color: var(--success-color);"></div>
                                </div>
                                <span style="font-size: 14px; color: #666;">85%</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                <span style="font-size: 14px; width: 60px;">4 stars</span>
                                <div style="flex: 1; height: 8px; background-color: #ddd; border-radius: 4px; overflow: hidden;">
                                    <div style="width: 10%; height: 100%; background-color: var(--success-color);"></div>
                                </div>
                                <span style="font-size: 14px; color: #666;">10%</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                <span style="font-size: 14px; width: 60px;">3 stars</span>
                                <div style="flex: 1; height: 8px; background-color: #ddd; border-radius: 4px; overflow: hidden;">
                                    <div style="width: 3%; height: 100%; background-color: var(--warning-color);"></div>
                                </div>
                                <span style="font-size: 14px; color: #666;">3%</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                <span style="font-size: 14px; width: 60px;">2 stars</span>
                                <div style="flex: 1; height: 8px; background-color: #ddd; border-radius: 4px; overflow: hidden;">
                                    <div style="width: 1%; height: 100%; background-color: var(--danger-color);"></div>
                                </div>
                                <span style="font-size: 14px; color: #666;">1%</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span style="font-size: 14px; width: 60px;">1 star</span>
                                <div style="flex: 1; height: 8px; background-color: #ddd; border-radius: 4px; overflow: hidden;">
                                    <div style="width: 1%; height: 100%; background-color: var(--danger-color);"></div>
                                </div>
                                <span style="font-size: 14px; color: #666;">1%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div>
                <!-- Booking Card -->
                <div style="background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); position: sticky; top: 100px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid var(--gray-light);">
                        <div>
                            <span style="font-size: 32px; font-weight: 700; color: var(--primary-color);"><?php echo formatPrice($room['base_price']); ?></span>
                            <span style="font-size: 14px; color: #666;">/night</span>
                        </div>
                        <div style="background-color: var(--success-color); color: white; padding: 8px 15px; border-radius: 20px; font-size: 13px;">
                            <?php echo count($availableRooms); ?> rooms available
                        </div>
                    </div>
                    
                    <!-- Quick Availability Check -->
                    <form action="availability.php" method="GET" style="margin-bottom: 25px;">
                        <input type="hidden" name="room_type" value="<?php echo $categoryId; ?>">
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Check-in</label>
                            <input type="date" name="check_in" min="<?php echo date('Y-m-d'); ?>" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-medium); border-radius: 5px;">
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Check-out</label>
                            <input type="date" name="check_out" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-medium); border-radius: 5px;">
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 10px;">
                            Check Availability
                        </button>
                    </form>
                    
                    <a href="booking.php?room=<?php echo $categoryId; ?>" class="btn btn-dark" style="width: 100%; display: block; text-align: center;">
                        Book Now
                    </a>
                    
                    <div style="margin-top: 25px; padding-top: 25px; border-top: 1px solid var(--gray-light);">
                        <p style="font-size: 14px; font-weight: 600; margin-bottom: 15px;">Room Features</p>
                        <ul style="list-style: none; font-size: 14px; color: #666;">
                            <li style="margin-bottom: 10px;"><i class="fas fa-user-friends" style="color: var(--primary-color); width: 25px;"></i> Max <?php echo $room['max_occupancy']; ?> guests</li>
                            <li style="margin-bottom: 10px;"><i class="fas fa-bed" style="color: var(--primary-color); width: 25px;"></i> <?php echo htmlspecialchars($room['bed_type']); ?></li>
                            <li style="margin-bottom: 10px;"><i class="fas fa-expand" style="color: var(--primary-color); width: 25px;"></i> <?php echo $room['room_size_sqm']; ?> m²</li>
                            <li><i class="fas fa-wifi" style="color: var(--primary-color); width: 25px;"></i> Free WiFi</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Similar Rooms -->
        <div style="margin-top: 60px;">
            <h2 style="font-size: 32px; margin-bottom: 30px; text-align: center;">Similar Rooms</h2>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px;">
                <?php foreach ($similarRooms as $index => $similar): ?>
                <div style="background-color: white; border-radius: 10px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.08);">
                    <div style="position: relative; height: 200px;">
                        <img src="https://images.unsplash.com/photo-<?php echo ['1590490360182-c33d57733427','1582719478250-c89cae141e86','1566666208517-13f42e1e3c2c'][$index % 3] ?>?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" style="width: 100%; height: 100%; object-fit: cover;">
                        <div style="position: absolute; top: 15px; right: 15px; background-color: var(--primary-color); color: white; padding: 8px 15px; border-radius: 5px; font-size: 14px; font-weight: 600;">
                            <?php echo formatPrice($similar['base_price']); ?>/night
                        </div>
                    </div>
                    <div style="padding: 25px;">
                        <h3 style="font-size: 20px; margin-bottom: 10px;"><?php echo htmlspecialchars($similar['category_name']); ?></h3>
                        <div style="display: flex; gap: 15px; margin-bottom: 15px; font-size: 13px; color: #666;">
                            <span><i class="fas fa-user" style="color: var(--primary-color);"></i> <?php echo $similar['max_occupancy']; ?></span>
                            <span><i class="fas fa-vector-square" style="color: var(--primary-color);"></i> <?php echo $similar['room_size_sqm']; ?> m²</span>
                        </div>
                        <a href="room-details.php?id=<?php echo $similar['category_id']; ?>" class="btn btn-outline" style="width: 100%; display: block; text-align: center;">View Details</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<script>
function changeImage(src) {
    document.getElementById('mainImage').src = src;
}
</script>

<?php require_once 'includes/footer.php'; ?>
