# 360° Virtual Tour System - Bayawan Bai Hotel

## Overview
This feature allows guests to explore hotel rooms in an immersive 360-degree panoramic view using the Pannellum library.

## Features
- **360° Panoramic Viewer**: Full rotation in all directions (left, right, up, down)
- **Interactive Hotspots**: Add clickable points with information, links, or scene navigation
- **Room Selection**: Easy navigation between different room categories
- **Mobile Responsive**: Works on desktop, tablet, and mobile devices
- **Admin Management**: Complete CRUD operations for virtual tours

## Database Tables

### room_virtual_tours
- `tour_id` - Primary key
- `category_id` - Links to room_categories
- `panorama_image` - Path to 360° equirectangular image
- `thumbnail_image` - Optional preview thumbnail
- `title` - Tour title
- `description` - Tour description
- `is_active` - Active status
- `display_order` - Sort order

### virtual_tour_hotspots
- `hotspot_id` - Primary key
- `tour_id` - Links to room_virtual_tours
- `hotspot_type` - Type: info, scene, or link
- `pitch` - Vertical angle (-90 to 90)
- `yaw` - Horizontal angle (-180 to 180)
- `text` - Tooltip text
- `target_tour_id` - For scene navigation
- `target_url` - For external links

## Installation

1. Run the SQL file to create tables:
   ```
   mysql -u root -p bayawan_hotel < database/virtual_tour_tables.sql
   ```

2. Ensure the uploads directory exists:
   ```
   assets/uploads/virtual_tours/
   ```

3. Access the admin panel at:
   ```
   admin/admin-virtual-tours.php
   ```

## Usage

### For Guests
- Navigate to `virtual-tour.php` from the main menu
- Select a room category from the sidebar
- Click and drag to look around
- Use scroll to zoom in/out
- Click fullscreen for best experience

### For Admins
1. **Add Virtual Tour**:
   - Go to Admin > Virtual Tours
   - Click "Add Virtual Tour"
   - Select room category
   - Upload 360° panorama image (equirectangular format, 2:1 ratio)
   - Add optional thumbnail and description

2. **Manage Hotspots**:
   - Click "Hotspots" on any tour
   - Add interactive points with pitch/yaw coordinates
   - Types: Info (tooltips), Scene (navigate to another tour), Link (external URL)

3. **Image Requirements**:
   - Format: Equirectangular projection
   - Aspect ratio: 2:1 (e.g., 4096x2048px)
   - File: JPG or PNG
   - Size: Optimize for web (under 5MB recommended)

## Image Sources

### Getting 360° Images:
1. **Professional Photography**: Hire a 360° photographer
2. **360° Camera**: Use cameras like Ricoh Theta, Insta360, or GoPro MAX
3. **Stock Images**: Purchase from stock photo sites
4. **AI Generation**: Use AI tools to generate room panoramas

### Sample Free 360° Resources:
- Flickr 360° groups
- Google Street View downloads (with permission)
- 360Cities.net

## Troubleshooting

### Images not loading
- Check file paths in database
- Ensure images are in `assets/uploads/virtual_tours/`
- Verify file permissions (755 for directories, 644 for files)

### Viewer not working
- Check browser console for JavaScript errors
- Ensure Pannellum CDN is accessible
- Verify image format is equirectangular

### Hotspots not appearing
- Check pitch/yaw values are within range
- Verify hotspot_config JSON is valid
- Check CSS is loaded properly

## Technical Details

### Pannellum Library
- Version: 2.5.6
- CDN: https://cdn.jsdelivr.net/npm/pannellum@2.5.6/
- Documentation: https://pannellum.org/documentation/

### Supported Features
- Equirectangular panoramas
- Cube maps (6-faced)
- Multi-resolution tiles
- Hotspots with custom HTML
- Auto-rotation
- Fullscreen mode
- Compass/north indicator

## Future Enhancements
- [ ] Multiple scene tours (room-to-room navigation)
- [ ] Audio hotspots (ambient room sounds)
- [ ] VR headset support (WebVR)
- [ ] Video panoramas (360° video)
- [ ] Floor plan integration
- [ ] Booking integration within tours

## Support
For issues or questions, contact the development team or refer to the Pannellum documentation.
