 <!-- 🟡 7. Order Confirmation -->
 <div x-show="currentScreen === 'confirmation'" 
 x-cloak
 x-transition:enter="transition ease-out duration-300"
 x-transition:enter-start="opacity-0 transform translate-x-full"
 x-transition:enter-end="opacity-100 transform translate-x-0"
 x-transition:leave="transition ease-in duration-200"
 x-transition:leave-start="opacity-100 transform translate-x-0"
 x-transition:leave-end="opacity-0 transform -translate-x-full"
 class="min-h-screen flex items-center justify-center bg-white">
<div class="w-full max-w-2xl px-6 text-center">
    <!-- Success Animation -->
    <div class="mb-12">
        <div class="w-32 h-32 bg-skin-base rounded-full mx-auto mb-6 flex items-center justify-center">
            <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
    </div>
    
    <h1 class="text-5xl font-bold text-gray-900 mb-6">{{ __('kiosk::modules.confirmation.title') }}</h1>
    <p class="text-2xl text-gray-600 mb-12">
        {{ __('kiosk::modules.confirmation.thank_you') }} {{ __('kiosk::modules.confirmation.order_number_prefix') }}
        <span class="font-bold text-skin-base ml-2" x-text="orderNumber"></span>
    </p>

    <!-- Order Details -->
    <div class="bg-white border border-gray-200 rounded-lg p-8 mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">{{ __('kiosk::modules.confirmation.order_details') }}</h2>
        <div class="space-y-4">
            <div class="flex justify-between items-center py-3 border-b border-gray-200">
                <span class="text-lg text-gray-600">{{ __('kiosk::modules.confirmation.order_type') }}</span>
                <span class="font-bold text-lg text-gray-900" 
                      x-text="orderType === 'dine-in' ? @js(__('kiosk::modules.menu.order_type.dine_in')) : (orderType === 'takeaway' ? @js(__('kiosk::modules.order_type.takeaway')) : @js(__('kiosk::modules.menu.order_type.delivery')))"></span>
            </div>
            
            <template x-if="orderType === 'dine-in'">
                <div class="flex justify-between items-center py-3 border-b border-gray-200">
                    <span class="text-lg text-gray-600">{{ __('kiosk::modules.confirmation.table_number') }}</span>
                    <span class="font-bold text-lg text-gray-900" x-text="tableNumber"></span>
                </div>
            </template>
            
            <template x-if="orderType === 'takeaway'">
                <div class="flex justify-between items-center py-3 border-b border-gray-200">
                    <span class="text-lg text-gray-600">{{ __('kiosk::modules.confirmation.estimated_pickup') }}</span>
                    <span class="font-bold text-lg text-gray-900" x-text="customerInfo.pickupTime + ' ' + @js(__('kiosk::modules.cart.minutes'))"></span>
                </div>
            </template>
            
            <div class="flex justify-between items-center py-3">
                <span class="text-lg text-gray-600">{{ __('kiosk::modules.confirmation.total_amount') }}</span>
                <span class="font-bold text-3xl text-skin-base" x-text="'$' + total"></span>
            </div>
        </div>
    </div>

    <!-- Additional Information -->
    <div class="bg-gray-50 rounded-lg p-8 mb-8">
        <div class="flex items-start space-x-4">
            <svg class="w-8 h-8 text-skin-base mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="text-left">
                <h3 class="font-bold text-gray-900 text-xl mb-4">{{ __('kiosk::modules.confirmation.whats_next') }}</h3>
                <ul class="text-lg text-gray-700 space-y-2">
                    <li>• {{ __('kiosk::modules.confirmation.next_sent_to_kitchen') }}</li>
                    <li>• {{ __('kiosk::modules.confirmation.next_updates') }}</li>
                    <li>• {{ __('kiosk::modules.confirmation.next_wait_number') }}</li>
                    <li>• {{ __('kiosk::modules.confirmation.next_receipt') }}</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Action Button -->
    <button @click="startNewOrder" 
        class="w-full bg-skin-base text-white py-6 px-8 rounded-lg font-bold text-2xl transition-all duration-200 hover:bg-skin-base">
        {{ __('kiosk::modules.confirmation.start_new_order') }}
    </button>
</div>
</div>