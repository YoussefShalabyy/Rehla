# 🗺️ Frontend Development Roadmap & Strategy

## 1. Readiness Check
**Are we ready?** Yes, 100%. 
The backend API is fully completed, secured, rate-limited, and tested. The `FRONTEND_INTEGRATION_GUIDE` and `postman_collection` provide everything the frontend team (or AI) needs to start immediately.

---

## 2. The Big Architecture Decision: Separate Apps vs. Super App
**Final Decision:** Build a **Single "Super App"** for both Customers and Owners (Providers), but keep the Admin Dashboard as a separate **Web App**.

### Why the Super App approach is better (Like Airbnb):
1. **Lower Acquisition Cost:** A customer booking a villa today might want to rent out their car tomorrow. If it's one app, they just click "Switch to Hosting" instead of downloading a whole new app.
2. **Maintenance:** You only maintain one React Native (Expo) codebase, one set of UI components, and one App Store listing.
3. **App Store Ranking:** All downloads, reviews, and traffic go to a single app, pushing it higher in search results.
4. **Backend Readiness:** Our backend is already designed for this. Users have a `role` field. The frontend simply hides/shows the "Provider Dashboard" bottom tab based on their role.

### Why Web for Admins?
Admin tasks involve viewing large data tables, moderating long reviews, and checking complex statistics. This is fundamentally a desktop/web experience. Do not clutter the mobile app with admin features.

---

## 3. Frontend Execution Roadmap

### Phase 1: The Core Mobile App (Customer First)
*Goal: Get the primary revenue-generating flow working.*
- **Step 1:** Setup Expo, Navigation (Stack + Tabs), and State Management (Zustand).
- **Step 2:** Auth Flow (Login, Register, Social Login placeholders).
- **Step 3:** Customer Home (Explore feed with Villas and Cars).
- **Step 4:** Listing Details & Search Filters.
- **Step 5:** Booking Flow & Payment WebView integration.
- **Step 6:** Customer Profile & Trips History.

### Phase 2: The "Host Mode" in Mobile App (Provider)
*Goal: Enable users to become providers and manage their assets.*
- **Step 1:** "Become a Host / Switch to Hosting" toggle in the profile.
- **Step 2:** Host Dashboard Tab (Revenue, Pending Bookings).
- **Step 3:** Listing Management (Add new property/car, upload photos via `multipart/form-data`).
- **Step 4:** Availability Calendar (Block/Unblock dates).

### Phase 3: The Admin Web Portal (Web Dashboard)
*Goal: Platform management and moderation.*
- **Tech Stack:** React (Vite) or Next.js + TailwindCSS.
- **Step 1:** Admin Auth (Login).
- **Step 2:** Global Stats Dashboard.
- **Step 3:** Listings Moderation Queue (Approve/Reject new listings).
- **Step 4:** User Management (Suspend/Activate).
- **Step 5:** Review Moderation.

---

## 4. Immediate Next Steps for You
1. Open a new folder for the mobile app (`npx create-expo-app vistastay-mobile`).
2. Feed the `MOBILE_UI_AI_PROMPT.md` to your preferred AI (Cursor/Stitch/v0).
3. Focus entirely on **Phase 1** (Customer Flow) first before getting distracted by the Owner or Admin features.
