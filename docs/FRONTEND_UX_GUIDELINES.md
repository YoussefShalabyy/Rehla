# VistaStay (Rehla) - UX & Product Guidelines

This document is the **official blueprint** for the Frontend (Mobile App & Web) based on the chosen **Unified Super App (Marketplace)** architecture. It details every single screen and tab behavior so the frontend developers can build it accurately.

---

## 1. Core Architecture (The Unified App)
The application is a single unified app. There is no separate "Host App". Every user downloads the same app, and the interface is designed to seamlessly convert regular users into property/car hosts.

### The Bottom Navigation Bar
The bottom bar is fixed and contains **5 Tabs**:
`[ Home | Wishlist | My Bookings | My Listings | Profile ]`

*(Note: "My Bookings" is for what the user is renting to use. "My Listings" is for what the user is offering to others).*

---

## 2. Detailed Breakdown of the 5 Tabs (Granular UI Specs)

### Tab 1: Home (Search & Explore)
**Purpose:** The default landing screen. Focused on discovery and conversion.
**Auth Requirement:** None (Lazy Auth).
**UI Elements:**
- **Top Header:** 
  - Sticky search bar: "Where are you going?" (Clicking opens a full-screen search/date picker).
- **Category Chips (Horizontal Scroll):** 
  - `[ All ]` `[ 🏢 Apartments ]` `[ 🏡 Villas ]` `[ 🚗 Cars ]` `[ 🏨 Hotels ]`
- **Main Feed (Vertical Scroll):**
  - Section Titles: "Popular Near You", "Top Rated Cars", "Luxury Villas".
- **The Listing Card (Crucial Component):**
  - **Image:** Swipeable carousel of photos (user can swipe through photos without leaving the home screen).
  - **Top Right Overlay:** Heart Icon (🤍 unselected, ❤️ selected) for Wishlist.
  - **Badges:** `⭐ 4.9 (120 reviews)` or `Top Choice`.
  - **Text Details:**
    - Title (e.g., "Luxury Sea-view Villa").
    - Location (e.g., "Alexandria, Sidi Gaber").
    - Key Amenities (e.g., "🛏 3 Beds • ❄️ AC • 📶 WiFi").
  - **Price:** Bold text "EGP 1,200" with smaller text "/ night" or "/ day".
- **Advanced Filters (Bottom Sheet):**
  - Opens when user clicks a "Filters" icon.
  - Includes: Price slider (Min/Max), Property Type, Bedrooms/Bathrooms count, Car Transmission (Auto/Manual), and specific Amenities checkboxes.

### Tab 2: Wishlist (المفضلة)
**Purpose:** Save properties/cars for later consideration.
**Auth Requirement:** Required. (Clicking this tab unauthenticated triggers the Login Modal).
**UI Elements:**
- **Empty State:** 
  - Illustration of a broken heart or an empty box.
  - Text: *"Nothing saved yet! Start exploring and heart your favorites."*
  - Button: `[ Explore Now ]` (Redirects to Home tab).
- **Filled State:** 
  - Grid view (2 columns) or List view of saved Listing Cards.
  - **Interaction:** Swiping left on a card removes it from the wishlist, or tapping the ❤️ icon toggles it off.

### Tab 3: My Bookings (حجوزاتي كعميل)
**Purpose:** Tracks reservations the user has made for themselves to consume.
**Auth Requirement:** Required.
**UI Elements:**
- **Top Segmented Control (Tabs):** 
  - `[ Active ]` `[ Upcoming ]` `[ Pending ]` `[ Past ]`
- **Empty State:** 
  - Illustration of luggage/car keys.
  - Text: *"No trips booked yet. Time to plan your next adventure!"*
- **Booking Card (List Item):**
  - Thumbnail image on the left.
  - Title and Location.
  - **Dates:** "12 Aug 2026 - 15 Aug 2026 (3 Nights)".
  - **Total Price:** "Paid: EGP 3,600".
  - **Status Badge:** 
    - `Confirmed` (Green)
    - `Active` (Blue - Currently checked-in)
    - `Pending` (Orange - Waiting for manual hotel confirmation)
    - `Cancelled` (Red)
- **Booking Details Screen (On Click):**
  - Full address with a prominent **[ Open in Google Maps ]** button.
  - Host/Provider contact block: Name, Avatar, and buttons for **[ Call ]** and **[ Message ]**.
  - Cancellation Policy text.
  - **[ Cancel Booking ]** button (only visible if within cancellation window).

### Tab 4: My Listings (لوحة تحكم المُضيف / Host Dashboard)
**Purpose:** The Provider Workspace. Where users manage what they are renting out.
**Auth Requirement:** Required.
**UI Elements:**
- **State A: The Empty State (Not a host yet)**
  - Massive, attractive hero graphic (e.g., someone handing over car keys or opening a house door).
  - Text: *"Turn your idle assets into cash! List your car or property in 3 easy steps."*
  - Big CTA Button: **[ + Add Your First Listing ]**.
- **State B: The Active Host State (Has listings)**
  - **Earnings Summary Card (Top):**
    - Large text: "This Month: EGP 15,000".
    - Subtext: "Pending payouts: EGP 2,000".
  - **Action Bar:**
    - Button: **[ + Add New Listing ]**.
  - **My Properties/Cars List:**
    - Each item shows a mini-thumbnail, title, and current dynamic price.
    - **Status Indicators:** 
      - `🟢 Published` (Live on app)
      - `🟡 Pending Approval` (Waiting for Admin review)
      - `🔴 Rejected` (Click to see reason, e.g., "Images too dark")
    - **Quick Toggles:** A switch to instantly turn a listing `Online / Offline` (if they want to temporarily hide it without deleting).
  - **Incoming Reservations (Section):**
    - A list of upcoming guests/renters who have booked their properties. Shows guest name, dates, and payout amount.
- **Listing Edit Screen (On Click):**
  - **Calendar Tab:** A visual monthly calendar where the host can tap days to "Block" them (make them unavailable for booking).
  - **Details Tab:** Edit description, upload new photos, change base price.

### Tab 5: Profile (الملف الشخصي)
**Purpose:** Account management and app settings.
**Auth Requirement:** Optional (Shows generic login button if unauthenticated).
**UI Elements:**
- **Header:** User Avatar (Click to change), Full Name, Email/Phone.
- **List Menu (Rows with > icons):**
  - 👤 **Personal Information:** Edit name, phone, password.
  - 💳 **Payment Methods:** Add/Remove Visa/Mastercard (for customers).
  - 🏦 **Payout Methods:** Add Bank Account details (for hosts to receive money).
  - 🌍 **Language:** English / العربية.
  - 🔔 **Notifications:** Toggle push notifications for bookings/messages.
  - 📞 **Help & Support:** FAQs, Contact Admin/Support.
  - 📄 **Legal:** Terms of Service, Privacy Policy.
- **Footer:** 
  - Version number (e.g., "v1.0.0").
  - Red **[ Logout ]** button.

---

## 3. Manual Hotel Bookings (Offline Workflow)
Hotels (unlike normal apartments or cars) might require manual confirmation by the Admin since there is no live Expedia/Booking.com API in the MVP.

**The Flow (Step-by-Step):**
1. **Admin lists the hotel:** The Admin adds the hotel from the Admin Web Dashboard and marks it as `Instant Book = False`.
2. **User requests it:** The user finds the hotel on the `Home` tab, selects dates, and clicks **"Request to Book"** (No money is charged yet).
3. **Status = Pending:** The booking goes into the user's `My Bookings -> Pending` tab.
4. **Admin Verification:** The Admin sees the request, manually calls the hotel to check if rooms are actually available for those dates.
5. **Approval:** 
   - If yes, Admin clicks `Confirm`. The user gets a push notification: *"Your hotel is available! Pay now to secure it."* 
   - If no, Admin clicks `Reject` and the user is notified that it's fully booked.

---

## 4. Frontend Rules (For Developers)
- **Money Formatting:** The API returns money in `cents` (e.g., `50000` = 500 EGP). The frontend MUST divide by 100 before displaying it to the user.
- **No Floating Modals for Full Screens:** The `My Listings` dashboard should feel like a native part of the app. Do not make it a web-view or a pop-up.
- **Dark Mode Support:** Ensure the UI variables support both light and dark modes inherently.
- **Image Requirements:** When a host uploads property images in `My Listings`, enforce a minimum of 3 images and compress them before sending them to the API.
