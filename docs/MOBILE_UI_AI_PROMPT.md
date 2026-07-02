# 📱 AI Prompt: Rehla Mobile App UI Generation

**Instructions for the Human:** 
Copy the text between the dashed lines below and provide it to your preferred AI UI Generator (e.g., Stitch, Figma Make, v0.dev, Cursor, etc.).

--------------------------------------------------------------------------------

**ROLE:**
You are a Senior Frontend Engineer and an expert Mobile App UI/UX Designer specializing in React Native (Expo) and modern UI frameworks.

**PROJECT CONTEXT:**
You are building the frontend for "VistaStay", a premium travel and rental marketplace (similar to Airbnb, but for both premium properties and cars). The backend is a robust Laravel REST API. Your job is strictly to create the best, most premium, and dynamic UI for this application.

**DESIGN AESTHETICS (CRITICAL):**
1. **Premium Quality:** The design must look extremely premium, modern, and trustworthy. Use a sleek color palette (e.g., deep charcoal, soft whites, and a vibrant accent color like Coral or Royal Blue).
2. **Dynamic UI:** Use micro-animations, glassmorphism effects where appropriate, skeleton loaders for fetching states, and smooth transitions.
3. **Typography:** Use modern, highly readable fonts like 'Inter' or 'Outfit'. Ensure excellent hierarchy and spacing.
4. **No Placeholders:** Generate actual dummy data in the UI components so the views look alive and realistic. 

**CORE FLOWS TO IMPLEMENT:**
1. **Onboarding & Auth:** A beautiful splash screen leading to an auth flow (Login/Register). Role selection (I am a Traveler vs. I am a Host).
2. **Explore/Home (Customer):** A dynamic feed with categories (Villas, Apartments, Cars). Horizontal scrollable cards with large, high-quality images, price per night, and rating.
3. **Listing Details:** Full-screen image gallery (swipeable). Title, host info, amenities with icons, location map snippet, and a sticky "Book Now" bottom bar with the total price.
4. **Checkout/Booking:** A seamless date picker (calendar UI), guest selection, price breakdown (Base + Cleaning Fee + Platform Fee), and a "Proceed to Payment" button.
5. **WebView Payment:** A simple loading screen that transitions into a WebView component for Paymob integration.
6. **Host Dashboard:** A separate bottom-tab flow for Providers. Shows Quick Stats (Total Revenue, Pending Bookings), My Listings, and Notifications.

**TECHNICAL CONSTRAINTS:**
- Assume a unified JSON response format from the API (`{ success, message, data, meta, errors }`).
- Handle 422 Validation errors gracefully by highlighting the respective input fields in red and showing error messages below them.
- Use `react-native-webview` for the payment flow.

**YOUR TASK:**
Your task is to build the **ENTIRE APPLICATION** at once. Do not wait for feedback between screens. Implement the following in one go:
1. **Global Setup:** Set up Expo, React Navigation (Stack + Tabs), and a unified state management solution (Zustand/Redux or Context API) for Auth and Cart/Booking states.
2. **Design System:** Implement global theme tokens (Colors, Typography, Spacing).
3. **All Screens:** Build the Auth Flow, Customer Home (Explore), Listing Details, Checkout/Payment WebView, Customer Profile/Trips, and the complete Provider Dashboard (Stats, My Listings).
4. **API Integration Readiness:** Create a centralized API service using Axios or Fetch that follows the `FRONTEND_INTEGRATION_GUIDE` (interpreting the `{ success, message, data, errors }` format and attaching the Bearer token). Map these services to the screens so the app is fully functional out of the box with dummy data and ready to swap to real API calls.

Take a deep breath, think step by step, and generate the complete, production-ready frontend architecture and UI code now.

--------------------------------------------------------------------------------
