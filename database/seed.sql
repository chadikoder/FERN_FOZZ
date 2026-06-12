-- Furn Fawz demo seed data
-- Safe to run more than once.

INSERT INTO categories (name, slug, is_active) VALUES
('Manakish', 'manakish', 1),
('Crepes', 'crepes', 1),
('Kaak', 'kaak', 1),
('Drinks', 'drinks', 1)
ON DUPLICATE KEY UPDATE
name = VALUES(name),
is_active = VALUES(is_active);

INSERT INTO products (category_id, name, slug, description, price, image_path, is_available) VALUES
((SELECT id FROM categories WHERE slug = 'manakish'), 'Cheese Manakish', 'cheese-manakish', 'Fresh cheese manakish with zaatar crust.', 6.00, 'uploads/products/cheese-manakish.jpg', 1),
((SELECT id FROM categories WHERE slug = 'manakish'), 'Zaatar Manakish', 'zaatar-manakish', 'Classic zaatar manakish with olive oil.', 5.00, 'uploads/products/zaatar-manakish.jpg', 1),
((SELECT id FROM categories WHERE slug = 'manakish'), 'Lahm bi Ajeen', 'lahm-bi-ajeen', 'Savory minced meat on thin crust.', 7.50, 'uploads/products/lahm-bi-ajeen.jpg', 1),
((SELECT id FROM categories WHERE slug = 'crepes'), 'Nutella Crepe', 'nutella-crepe', 'Warm crepe with Nutella and banana.', 8.50, 'uploads/products/nutella-crepe.jpg', 1),
((SELECT id FROM categories WHERE slug = 'crepes'), 'Chicken Crepe', 'chicken-crepe', 'Savory chicken crepe with fresh herbs.', 9.00, 'uploads/products/chicken-crepe.jpg', 1),
((SELECT id FROM categories WHERE slug = 'kaak'), 'Kaak with Cheese', 'kaak-with-cheese', 'Soft kaak bread stuffed with cheese.', 5.50, 'uploads/products/kaak-with-cheese.jpg', 1),
((SELECT id FROM categories WHERE slug = 'drinks'), 'Soft Drink', 'soft-drink', 'Refreshing soda drink.', 2.50, 'uploads/products/soft-drink.jpg', 1),
((SELECT id FROM categories WHERE slug = 'drinks'), 'Water Bottle', 'water-bottle', '500ml bottled water.', 1.50, 'uploads/products/water-bottle.jpg', 1)
ON DUPLICATE KEY UPDATE
category_id = VALUES(category_id),
name = VALUES(name),
description = VALUES(description),
price = VALUES(price),
image_path = VALUES(image_path),
is_available = VALUES(is_available),
updated_at = NOW();

-- Example admin user for local development: admin@furnfawz.com / admin123
INSERT INTO admins (name, email, password_hash) VALUES
('Fares', 'admin@furnfawz.com', '$2y$10$05bccxJPNlqFciLAFtz6xeKTzNM9K0T7kMA8KML3a6XgL9XQE34sm')
ON DUPLICATE KEY UPDATE
name = VALUES(name);

INSERT INTO settings (name, value) VALUES
('owner_phone', '971501234567')
ON DUPLICATE KEY UPDATE
value = VALUES(value);
