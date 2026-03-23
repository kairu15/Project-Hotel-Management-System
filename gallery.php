<?php
$pageTitle = 'Photo Gallery';
require_once 'includes/header.php';

// Get gallery images from database
$db = getDB();
$images = $db->query("SELECT * FROM gallery WHERE is_featured = 1 ORDER BY sort_order, uploaded_at DESC")->fetchAll();

// Categories for filtering
$categories = ['all', 'rooms', 'dining', 'amenities', 'hotel', 'attractions'];
$activeCategory = $_GET['category'] ?? 'all';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>Photo Gallery</h1>
        <p>Explore Bayawan Bai Hotel through our gallery</p>
    </div>
</div>

<!-- Gallery Section -->
<section style="padding: 60px 0; background-color: var(--gray-light);">
    <div class="container">
        <!-- Filter Tabs -->
        <div style="text-align: center; margin-bottom: 40px;">
            <div style="display: inline-flex; gap: 10px; background-color: white; padding: 8px; border-radius: 30px;">
                <a href="?category=all" style="padding: 12px 25px; border-radius: 25px; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.3s; <?php echo $activeCategory === 'all' ? 'background-color: var(--primary-color); color: white;' : 'color: #666;'; ?>">All Photos</a>
                <a href="?category=rooms" style="padding: 12px 25px; border-radius: 25px; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.3s; <?php echo $activeCategory === 'rooms' ? 'background-color: var(--primary-color); color: white;' : 'color: #666;'; ?>">Rooms</a>
                <a href="?category=amenities" style="padding: 12px 25px; border-radius: 25px; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.3s; <?php echo $activeCategory === 'amenities' ? 'background-color: var(--primary-color); color: white;' : 'color: #666;'; ?>">Amenities</a>
                <a href="?category=dining" style="padding: 12px 25px; border-radius: 25px; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.3s; <?php echo $activeCategory === 'dining' ? 'background-color: var(--primary-color); color: white;' : 'color: #666;'; ?>">Dining</a>
                <a href="?category=attractions" style="padding: 12px 25px; border-radius: 25px; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.3s; <?php echo $activeCategory === 'attractions' ? 'background-color: var(--primary-color); color: white;' : 'color: #666;'; ?>">Attractions</a>
            </div>
        </div>
        
        <!-- Gallery Grid -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
            <!-- Hotel Exterior -->
            <div class="gallery-item" data-category="hotel" style="grid-column: span 2; grid-row: span 2; position: relative; border-radius: 10px; overflow: hidden; cursor: pointer; height: 500px;" onclick="openLightbox('https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', 'Hotel Exterior')">
                <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                <div style="position: absolute; inset: 0; background: linear-gradient(transparent 50%, rgba(0,0,0,0.7)); display: flex; flex-direction: column; justify-content: flex-end; padding: 25px; opacity: 0; transition: opacity 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0'">
                    <h4 style="color: white; font-size: 20px; margin-bottom: 5px;">Hotel Exterior</h4>
                    <p style="color: rgba(255,255,255,0.8); font-size: 14px; margin: 0;"><i class="fas fa-search-plus"></i> Click to view</p>
                </div>
            </div>
            
            <!-- Standard Room -->
            <div class="gallery-item" data-category="rooms" style="position: relative; border-radius: 10px; overflow: hidden; cursor: pointer; height: 240px;" onclick="openLightbox('https://images.unsplash.com/photo-1631049307260-da0c0f11336a?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', 'Standard Room')">
                <img src="https://images.unsplash.com/photo-1631049307260-da0c0f11336a?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                <div style="position: absolute; inset: 0; background: linear-gradient(transparent 50%, rgba(0,0,0,0.7)); display: flex; flex-direction: column; justify-content: flex-end; padding: 20px; opacity: 0; transition: opacity 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0'">
                    <h4 style="color: white; font-size: 16px; margin: 0;">Standard Room</h4>
                </div>
            </div>
            
            <!-- Pool -->
            <div class="gallery-item" data-category="amenities" style="position: relative; border-radius: 10px; overflow: hidden; cursor: pointer; height: 240px;" onclick="openLightbox('https://images.unsplash.com/photo-1576013551627-0cc20b96c2c?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', 'Infinity Pool')">
                <img src="https://images.unsplash.com/photo-1576013551627-0cc20b96c2c?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                <div style="position: absolute; inset: 0; background: linear-gradient(transparent 50%, rgba(0,0,0,0.7)); display: flex; flex-direction: column; justify-content: flex-end; padding: 20px; opacity: 0; transition: opacity 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0'">
                    <h4 style="color: white; font-size: 16px; margin: 0;">Infinity Pool</h4>
                </div>
            </div>
            
            <!-- Restaurant -->
            <div class="gallery-item" data-category="dining" style="position: relative; border-radius: 10px; overflow: hidden; cursor: pointer; height: 240px;" onclick="openLightbox('https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', 'Restaurant')">
                <img src="https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                <div style="position: absolute; inset: 0; background: linear-gradient(transparent 50%, rgba(0,0,0,0.7)); display: flex; flex-direction: column; justify-content: flex-end; padding: 20px; opacity: 0; transition: opacity 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0'">
                    <h4 style="color: white; font-size: 16px; margin: 0;">Restaurant</h4>
                </div>
            </div>
            
            <!-- Deluxe Room -->
            <div class="gallery-item" data-category="rooms" style="position: relative; border-radius: 10px; overflow: hidden; cursor: pointer; height: 240px;" onclick="openLightbox('https://images.unsplash.com/photo-1566666208517-13f42e1e3c2c?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', 'Deluxe Room')">
                <img src="https://images.unsplash.com/photo-1566666208517-13f42e1e3c2c?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                <div style="position: absolute; inset: 0; background: linear-gradient(transparent 50%, rgba(0,0,0,0.7)); display: flex; flex-direction: column; justify-content: flex-end; padding: 20px; opacity: 0; transition: opacity 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0'">
                    <h4 style="color: white; font-size: 16px; margin: 0;">Deluxe Room</h4>
                </div>
            </div>
            
            <!-- Suite -->
            <div class="gallery-item" data-category="rooms" style="grid-column: span 2; position: relative; border-radius: 10px; overflow: hidden; cursor: pointer; height: 240px;" onclick="openLightbox('https://images.unsplash.com/photo-1590490360182-c33d57733427?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', 'Presidential Suite')">
                <img src="https://images.unsplash.com/photo-1590490360182-c33d57733427?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                <div style="position: absolute; inset: 0; background: linear-gradient(transparent 50%, rgba(0,0,0,0.7)); display: flex; flex-direction: column; justify-content: flex-end; padding: 20px; opacity: 0; transition: opacity 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0'">
                    <h4 style="color: white; font-size: 18px; margin: 0;">Presidential Suite</h4>
                </div>
            </div>
            
            <!-- Spa -->
            <div class="gallery-item" data-category="amenities" style="position: relative; border-radius: 10px; overflow: hidden; cursor: pointer; height: 240px;" onclick="openLightbox('https://images.unsplash.com/photo-1540555700478-4be289fbecef?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', 'Spa')">
                <img src="https://images.unsplash.com/photo-1540555700478-4be289fbecef?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                <div style="position: absolute; inset: 0; background: linear-gradient(transparent 50%, rgba(0,0,0,0.7)); display: flex; flex-direction: column; justify-content: flex-end; padding: 20px; opacity: 0; transition: opacity 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0'">
                    <h4 style="color: white; font-size: 16px; margin: 0;">Spa</h4>
                </div>
            </div>
            
            <!-- Danjugan Island -->
            <div class="gallery-item" data-category="attractions" style="position: relative; border-radius: 10px; overflow: hidden; cursor: pointer; height: 240px;" onclick="openLightbox('https://images.unsplash.com/photo-1582719478250-c89cae141e86?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', 'Danjugan Island')">
                <img src="https://images.unsplash.com/photo-1582719478250-c89cae141e86?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                <div style="position: absolute; inset: 0; background: linear-gradient(transparent 50%, rgba(0,0,0,0.7)); display: flex; flex-direction: column; justify-content: flex-end; padding: 20px; opacity: 0; transition: opacity 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0'">
                    <h4 style="color: white; font-size: 16px; margin: 0;">Danjugan Island</h4>
                </div>
            </div>
            
            <!-- Beach -->
            <div class="gallery-item" data-category="attractions" style="position: relative; border-radius: 10px; overflow: hidden; cursor: pointer; height: 240px;" onclick="openLightbox('https://images.unsplash.com/photo-1507525428034-b723cf961d3e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80', 'Bayawan Bay Beach')">
                <img src="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                <div style="position: absolute; inset: 0; background: linear-gradient(transparent 50%, rgba(0,0,0,0.7)); display: flex; flex-direction: column; justify-content: flex-end; padding: 20px; opacity: 0; transition: opacity 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0'">
                    <h4 style="color: white; font-size: 16px; margin: 0;">Bayawan Bay Beach</h4>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Lightbox -->
<div id="lightbox" style="display: none; position: fixed; inset: 0; background-color: rgba(0,0,0,0.95); z-index: 9999; justify-content: center; align-items: center;">
    <button onclick="closeLightbox()" style="position: absolute; top: 20px; right: 20px; background: none; border: none; color: white; font-size: 30px; cursor: pointer;">
        <i class="fas fa-times"></i>
    </button>
    <img id="lightboxImage" src="" style="max-width: 90%; max-height: 90%; object-fit: contain;">
    <p id="lightboxCaption" style="position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); color: white; font-size: 18px;"></p>
</div>

<script>
function openLightbox(src, caption) {
    document.getElementById('lightboxImage').src = src;
    document.getElementById('lightboxCaption').textContent = caption;
    document.getElementById('lightbox').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    document.getElementById('lightbox').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeLightbox();
    }
});

// Close on click outside
document.getElementById('lightbox').addEventListener('click', function(e) {
    if (e.target === this) {
        closeLightbox();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
