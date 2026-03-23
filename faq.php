<?php
$pageTitle = 'Frequently Asked Questions';
require_once 'includes/header.php';

// Get FAQs from database
$db = getDB();
$faqs = $db->query("SELECT * FROM faqs WHERE is_active = 1 ORDER BY sort_order, faq_id")->fetchAll();

// Group FAQs by category
$groupedFaqs = [];
foreach ($faqs as $faq) {
    $category = $faq['category'] ?? 'general';
    $groupedFaqs[$category][] = $faq;
}

$categoryLabels = [
    'reservations' => 'Reservations & Bookings',
    'dining' => 'Dining',
    'services' => 'Services & Amenities',
    'payments' => 'Payments',
    'policies' => 'Policies',
    'location' => 'Location & Transportation',
    'amenities' => 'Spa & Amenities',
    'general' => 'General Questions'
];
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>Frequently Asked Questions</h1>
        <p>Find answers to common questions about Bayawan Bai Hotel</p>
    </div>
</div>

<!-- FAQ Section -->
<section style="padding: 60px 0; background-color: var(--gray-light);">
    <div class="container">
        <div style="display: grid; grid-template-columns: 300px 1fr; gap: 40px;">
            <!-- Sidebar Navigation -->
            <div>
                <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); position: sticky; top: 100px;">
                    <h3 style="font-size: 18px; margin-bottom: 20px;">Categories</h3>
                    <ul style="list-style: none; padding: 0;">
                        <?php foreach ($groupedFaqs as $category => $items): ?>
                        <li style="margin-bottom: 10px;">
                            <a href="#<?php echo $category; ?>" style="display: block; padding: 10px 15px; color: #666; text-decoration: none; border-radius: 5px; transition: all 0.3s;" 
                                onmouseover="this.style.backgroundColor='var(--gray-light)'; this.style.color='var(--primary-color)';" 
                                onmouseout="this.style.backgroundColor='transparent'; this.style.color='#666';">
                                <?php echo $categoryLabels[$category] ?? ucfirst($category); ?>
                                <span style="float: right; background-color: var(--gray-light); color: #666; padding: 2px 8px; border-radius: 10px; font-size: 12px;"><?php echo count($items); ?></span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--gray-light);">
                        <p style="font-size: 14px; color: #666; margin-bottom: 15px;">Can't find what you're looking for?</p>
                        <a href="contact.php" class="btn btn-primary btn-sm" style="width: 100%;">Contact Us</a>
                    </div>
                </div>
            </div>
            
            <!-- FAQ Content -->
            <div>
                <?php foreach ($groupedFaqs as $category => $items): ?>
                <div id="<?php echo $category; ?>" style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; overflow: hidden;">
                    <div style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; padding: 20px 30px;">
                        <h3 style="font-size: 20px; color: white; margin: 0;">
                            <i class="fas fa-folder-open" style="margin-right: 10px;"></i>
                            <?php echo $categoryLabels[$category] ?? ucfirst($category); ?>
                        </h3>
                    </div>
                    <div style="padding: 0;">
                        <?php foreach ($items as $index => $faq): 
                            $faqId = 'faq-' . $category . '-' . $index;
                        ?>
                        <div style="border-bottom: 1px solid var(--gray-light);">
                            <button onclick="toggleFaq('<?php echo $faqId; ?>')" style="width: 100%; padding: 20px 30px; background: none; border: none; text-align: left; cursor: pointer; display: flex; justify-content: space-between; align-items: center; font-size: 16px; font-weight: 600; color: var(--dark-color); transition: background-color 0.3s;" onmouseover="this.style.backgroundColor='var(--gray-light)'" onmouseout="if(!document.getElementById('<?php echo $faqId; ?>').classList.contains('active')) this.style.backgroundColor='transparent'">
                                <span><?php echo htmlspecialchars($faq['question']); ?></span>
                                <i class="fas fa-chevron-down" id="icon-<?php echo $faqId; ?>" style="color: var(--primary-color); transition: transform 0.3s;"></i>
                            </button>
                            <div id="<?php echo $faqId; ?>" style="display: none; padding: 0 30px 20px;">
                                <p style="color: #666; line-height: 1.8; margin: 0;"><?php echo nl2br(htmlspecialchars($faq['answer'])); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <!-- Still Have Questions -->
                <div style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); padding: 50px; border-radius: 10px; text-align: center; color: white;">
                    <i class="fas fa-headset" style="font-size: 50px; margin-bottom: 20px; opacity: 0.8;"></i>
                    <h3 style="font-size: 28px; color: white; margin-bottom: 15px;">Still Have Questions?</h3>
                    <p style="font-size: 16px; opacity: 0.9; margin-bottom: 30px; max-width: 500px; margin-left: auto; margin-right: auto;">
                        Our friendly team is here to help. Contact us by phone, email, or through our contact form.
                    </p>
                    <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
                        <a href="tel:+63351234567" style="background-color: white; color: var(--primary-color); padding: 15px 30px; border-radius: 5px; text-decoration: none; font-weight: 600;">
                            <i class="fas fa-phone" style="margin-right: 8px;"></i>Call Us
                        </a>
                        <a href="contact.php" style="background-color: transparent; color: white; padding: 15px 30px; border-radius: 5px; text-decoration: none; font-weight: 600; border: 2px solid white;">
                            <i class="fas fa-envelope" style="margin-right: 8px;"></i>Send Message
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function toggleFaq(id) {
    const content = document.getElementById(id);
    const icon = document.getElementById('icon-' + id);
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        content.classList.add('active');
        icon.style.transform = 'rotate(180deg)';
    } else {
        content.style.display = 'none';
        content.classList.remove('active');
        icon.style.transform = 'rotate(0deg)';
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
