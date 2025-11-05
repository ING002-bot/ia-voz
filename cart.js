// ============================================
// SISTEMA DE CARRITO DE COMPRAS
// Farmacia Omarcitoia - 2025
// ============================================

(function() {
  'use strict';
  
  // Estado del carrito (almacenado en localStorage)
  let cart = [];
  
  // Elementos del DOM
  const cartFab = document.getElementById('cartFab');
  const cartSidebar = document.getElementById('cartSidebar');
  const cartOverlay = document.getElementById('cartOverlay');
  const closeCart = document.getElementById('closeCart');
  const cartItems = document.getElementById('cartItems');
  const cartTotal = document.getElementById('cartTotal');
  const cartCount = document.getElementById('cartCount');
  const checkoutBtn = document.getElementById('checkoutBtn');
  const addToCartButtons = document.querySelectorAll('.btn-add-cart');
  
  // ============================================
  // FUNCIONES DE CARRITO
  // ============================================
  
  // Cargar carrito desde localStorage
  function loadCart() {
    const saved = localStorage.getItem('omarcitoia_cart');
    if (saved) {
      try {
        cart = JSON.parse(saved);
        console.log('🛒 Carrito cargado:', cart);
      } catch (e) {
        console.error('Error al cargar carrito:', e);
        cart = [];
      }
    }
    updateCartUI();
  }
  
  // Guardar carrito en localStorage
  function saveCart() {
    localStorage.setItem('omarcitoia_cart', JSON.stringify(cart));
    console.log('💾 Carrito guardado');
  }
  
  // Agregar producto al carrito
  function addToCart(product) {
    const existingItem = cart.find(item => item.id === product.id);
    
    if (existingItem) {
      // Verificar stock disponible
      if (existingItem.cantidad < product.stock) {
        existingItem.cantidad++;
        showNotification(`✅ ${product.nombre} agregado (${existingItem.cantidad})`, 'success');
      } else {
        showNotification(`⚠️ Stock máximo alcanzado para ${product.nombre}`, 'warning');
        return;
      }
    } else {
      cart.push({
        id: product.id,
        nombre: product.nombre,
        precio: product.precio,
        cantidad: 1,
        stock: product.stock
      });
      showNotification(`✅ ${product.nombre} agregado al carrito`, 'success');
    }
    
    saveCart();
    updateCartUI();
  }
  
  // Remover producto del carrito
  function removeFromCart(productId) {
    const index = cart.findIndex(item => item.id === productId);
    if (index !== -1) {
      const removedItem = cart[index];
      cart.splice(index, 1);
      saveCart();
      updateCartUI();
      showNotification(`🗑️ ${removedItem.nombre} eliminado del carrito`, 'info');
    }
  }
  
  // Actualizar cantidad
  function updateQuantity(productId, delta) {
    const item = cart.find(item => item.id === productId);
    if (!item) return;
    
    const newQuantity = item.cantidad + delta;
    
    if (newQuantity <= 0) {
      removeFromCart(productId);
    } else if (newQuantity <= item.stock) {
      item.cantidad = newQuantity;
      saveCart();
      updateCartUI();
    } else {
      showNotification(`⚠️ Stock máximo: ${item.stock} unidades`, 'warning');
    }
  }
  
  // Calcular total
  function calculateTotal() {
    return cart.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);
  }
  
  // Actualizar UI del carrito
  function updateCartUI() {
    // Actualizar contador
    const totalItems = cart.reduce((sum, item) => sum + item.cantidad, 0);
    cartCount.textContent = totalItems;
    
    if (totalItems > 0) {
      cartCount.style.display = 'flex';
    } else {
      cartCount.style.display = 'none';
    }
    
    // Actualizar total
    const total = calculateTotal();
    cartTotal.textContent = `S/ ${total.toFixed(2)}`;
    
    // Actualizar widget de resumen
    updateCartWidget(totalItems, total);
    
    // Actualizar items
    if (cart.length === 0) {
      cartItems.innerHTML = `
        <div class="empty-cart">
          <div class="empty-icon">🛍️</div>
          <p>Tu carrito está vacío</p>
          <small>Agrega productos para comenzar</small>
        </div>
      `;
      checkoutBtn.disabled = true;
    } else {
      cartItems.innerHTML = cart.map(item => `
        <div class="cart-item" data-id="${item.id}">
          <div class="item-info">
            <h4>${item.nombre}</h4>
            <p class="item-price">S/ ${item.precio.toFixed(2)} c/u</p>
          </div>
          <div class="item-actions">
            <div class="quantity-controls">
              <button class="btn-qty" onclick="window.cartSystem.updateQuantity(${item.id}, -1)">−</button>
              <span class="quantity">${item.cantidad}</span>
              <button class="btn-qty" onclick="window.cartSystem.updateQuantity(${item.id}, 1)">+</button>
            </div>
            <div class="item-subtotal">
              S/ ${(item.precio * item.cantidad).toFixed(2)}
            </div>
            <button class="btn-remove" onclick="window.cartSystem.removeFromCart(${item.id})">🗑️</button>
          </div>
        </div>
      `).join('');
      checkoutBtn.disabled = false;
    }
  }
  
  // ============================================
  // WIDGET DE RESUMEN
  // ============================================
  
  function updateCartWidget(totalItems, total) {
    const widgetItemCount = document.getElementById('widgetItemCount');
    const widgetTotal = document.getElementById('widgetTotal');
    const cartSummaryWidget = document.getElementById('cartSummaryWidget');
    
    if (widgetItemCount) {
      const statNumber = widgetItemCount.querySelector('.stat-number');
      const statLabel = widgetItemCount.querySelector('.stat-label');
      if (statNumber) statNumber.textContent = totalItems;
      if (statLabel) statLabel.textContent = totalItems === 1 ? 'producto' : 'productos';
    }
    
    if (widgetTotal) {
      widgetTotal.textContent = `S/ ${total.toFixed(2)}`;
    }
    
    // Mostrar/ocultar widget según si hay items
    if (cartSummaryWidget) {
      if (totalItems > 0) {
        cartSummaryWidget.classList.add('visible');
        cartSummaryWidget.classList.remove('hidden');
      } else {
        cartSummaryWidget.classList.remove('visible');
        cartSummaryWidget.classList.add('hidden');
      }
    }
  }
  
  // ============================================
  // NOTIFICACIONES
  // ============================================
  
  function showNotification(message, type = 'info') {
    // Remover notificaciones anteriores
    const existing = document.querySelector('.cart-notification');
    if (existing) {
      existing.remove();
    }
    
    const notification = document.createElement('div');
    notification.className = `cart-notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    // Animar entrada
    setTimeout(() => {
      notification.classList.add('show');
    }, 10);
    
    // Remover después de 3 segundos
    setTimeout(() => {
      notification.classList.remove('show');
      setTimeout(() => {
        notification.remove();
      }, 300);
    }, 3000);
  }
  
  // ============================================
  // CHECKOUT Y COMPRA
  // ============================================
  
  async function processCheckout() {
    if (cart.length === 0) {
      showNotification('⚠️ El carrito está vacío', 'warning');
      return;
    }
    
    // Verificar si el usuario está logueado
    try {
      const response = await fetch('compra_api.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'check_session'
        })
      });
      
      const data = await response.json();
      
      if (!data.logged_in) {
        // Usuario no logueado - redirigir a login
        if (confirm('Debes iniciar sesión para realizar una compra.\n\n¿Deseas ir a iniciar sesión ahora?')) {
          // Guardar carrito antes de redirigir
          saveCart();
          window.location.href = 'login_unified.php';
        }
        return;
      }
      
      // Usuario logueado - proceder con la compra
      await realizarCompra(data.user_info);
      
    } catch (error) {
      console.error('Error al verificar sesión:', error);
      showNotification('❌ Error al procesar. Intenta de nuevo.', 'error');
    }
  }
  
  async function realizarCompra(userInfo) {
    // Mostrar loading
    checkoutBtn.disabled = true;
    checkoutBtn.innerHTML = '⏳ Procesando...';
    
    try {
      const response = await fetch('compra_api.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'process_purchase',
          items: cart
        })
      });
      
      const data = await response.json();
      
      if (data.success) {
        showNotification('✅ ¡Compra realizada con éxito!', 'success');
        
        // Generar PDF de la boleta
        generatePDF(data.boleta, userInfo);
        
        // Limpiar carrito
        cart = [];
        saveCart();
        updateCartUI();
        
        // Cerrar sidebar
        setTimeout(() => {
          closeSidebar();
        }, 1500);
        
      } else {
        showNotification(`❌ ${data.error || 'Error al procesar la compra'}`, 'error');
      }
      
    } catch (error) {
      console.error('Error al realizar compra:', error);
      showNotification('❌ Error al procesar la compra', 'error');
    } finally {
      checkoutBtn.disabled = false;
      checkoutBtn.innerHTML = '💳 Proceder al Pago';
    }
  }
  
  // ============================================
  // GENERACIÓN DE PDF
  // ============================================
  
  function generatePDF(boleta, userInfo) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    
    // Colores
    const azul = [30, 136, 229];
    const naranja = [251, 140, 0];
    
    // Encabezado
    doc.setFillColor(...azul);
    doc.rect(0, 0, 210, 45, 'F');
    
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(28);
    doc.setFont('helvetica', 'bold');
    doc.text('Farmacia Omarcitoia', 105, 20, { align: 'center' });
    
    doc.setFontSize(12);
    doc.setFont('helvetica', 'normal');
    doc.text('Tu salud, nuestra prioridad', 105, 30, { align: 'center' });
    doc.text('RUC: 20123456789 - Telf: (01) 234-5678', 105, 37, { align: 'center' });
    
    // Título de boleta
    doc.setTextColor(0, 0, 0);
    doc.setFontSize(18);
    doc.setFont('helvetica', 'bold');
    doc.text('BOLETA DE VENTA ELECTRÓNICA', 105, 58, { align: 'center' });
    
    // Información de la boleta
    doc.setFontSize(11);
    doc.setFont('helvetica', 'normal');
    doc.text(`N° Boleta: ${boleta.numero_boleta}`, 20, 70);
    doc.text(`Fecha: ${new Date(boleta.fecha).toLocaleDateString('es-PE', { 
      year: 'numeric', 
      month: 'long', 
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })}`, 20, 77);
    doc.text(`Cliente: ${userInfo.nombre}`, 20, 84);
    doc.text(`Usuario: @${userInfo.username}`, 20, 91);
    
    // Línea separadora
    doc.setDrawColor(...naranja);
    doc.setLineWidth(0.8);
    doc.line(20, 98, 190, 98);
    
    // Encabezados de tabla
    let y = 108;
    doc.setFillColor(240, 240, 240);
    doc.rect(20, y - 5, 170, 8, 'F');
    
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(10);
    doc.text('Producto', 22, y);
    doc.text('Cant.', 130, y);
    doc.text('P. Unit.', 150, y);
    doc.text('Subtotal', 175, y);
    
    // Productos
    y += 10;
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(9);
    
    const productos = JSON.parse(boleta.detalles);
    productos.forEach((item, index) => {
      // Alternar color de fondo
      if (index % 2 === 0) {
        doc.setFillColor(250, 250, 250);
        doc.rect(20, y - 4, 170, 7, 'F');
      }
      
      doc.text(item.nombre.substring(0, 40), 22, y);
      doc.text(item.cantidad.toString(), 133, y);
      doc.text(`S/ ${item.precio.toFixed(2)}`, 150, y);
      doc.text(`S/ ${item.subtotal.toFixed(2)}`, 175, y);
      y += 7;
    });
    
    // Línea antes del total
    y += 5;
    doc.setDrawColor(...azul);
    doc.setLineWidth(0.5);
    doc.line(20, y, 190, y);
    
    // Total
    y += 10;
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(14);
    doc.text('TOTAL:', 150, y);
    doc.setTextColor(...naranja);
    doc.setFontSize(16);
    doc.text(`S/ ${boleta.total.toFixed(2)}`, 175, y);
    
    // Información adicional
    doc.setTextColor(100, 100, 100);
    doc.setFontSize(9);
    doc.setFont('helvetica', 'normal');
    y += 15;
    doc.text('Información importante:', 20, y);
    y += 5;
    doc.setFontSize(8);
    doc.text('• Conserve este documento como comprobante de su compra', 22, y);
    y += 4;
    doc.text('• Ganaste ' + Math.floor(boleta.total / 10) + ' puntos con esta compra', 22, y);
    y += 4;
    doc.text('• Para consultas o reclamos comuníquese al (01) 234-5678', 22, y);
    
    // Footer
    y += 15;
    doc.setFillColor(...azul);
    doc.rect(0, y, 210, 30, 'F');
    
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(11);
    doc.setFont('helvetica', 'bold');
    doc.text('Gracias por su compra en Farmacia Omarcitoia', 105, y + 10, { align: 'center' });
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(9);
    doc.text('www.farmaciaomarcitoia.com | info@omarcitoia.com', 105, y + 17, { align: 'center' });
    
    // Descargar PDF
    doc.save(`Boleta_${boleta.numero_boleta}.pdf`);
    
    showNotification('📄 PDF descargado correctamente', 'success');
  }
  
  // ============================================
  // UI DEL SIDEBAR
  // ============================================
  
  function openSidebar() {
    cartSidebar.classList.add('open');
    cartOverlay.classList.add('show');
    document.body.style.overflow = 'hidden';
  }
  
  function closeSidebar() {
    cartSidebar.classList.remove('open');
    cartOverlay.classList.remove('show');
    document.body.style.overflow = '';
  }
  
  // ============================================
  // EVENT LISTENERS
  // ============================================
  
  // Abrir carrito
  if (cartFab) {
    cartFab.addEventListener('click', openSidebar);
  }
  
  // Cerrar carrito
  if (closeCart) {
    closeCart.addEventListener('click', closeSidebar);
  }
  
  if (cartOverlay) {
    cartOverlay.addEventListener('click', closeSidebar);
  }
  
  // Botones de agregar al carrito
  addToCartButtons.forEach(button => {
    if (!button.disabled) {
      button.addEventListener('click', function() {
        const product = {
          id: parseInt(this.dataset.id),
          nombre: this.dataset.nombre,
          precio: parseFloat(this.dataset.precio),
          stock: parseInt(this.dataset.stock)
        };
        addToCart(product);
      });
    }
  });
  
  // Checkout
  if (checkoutBtn) {
    checkoutBtn.addEventListener('click', processCheckout);
  }
  
  // Cerrar con ESC
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && cartSidebar.classList.contains('open')) {
      closeSidebar();
    }
  });
  
  // Botón del widget de resumen
  const widgetViewCart = document.getElementById('widgetViewCart');
  if (widgetViewCart) {
    widgetViewCart.addEventListener('click', openSidebar);
  }
  
  // ============================================
  // INICIALIZACIÓN
  // ============================================
  
  function init() {
    loadCart();
    console.log('🛒 Sistema de carrito inicializado');
  }
  
  // Limpiar carrito
  function clearCart() {
    if (confirm('¿Estás seguro de que quieres vaciar el carrito?')) {
      cart = [];
      saveCart();
      updateCartUI();
      showNotification('🗑️ Carrito vaciado correctamente', 'info');
      closeSidebar();
    }
  }
  
  // Exponer funciones globalmente
  window.cartSystem = {
    addToCart,
    removeFromCart,
    updateQuantity,
    openSidebar,
    closeSidebar,
    clearCart
  };
  
  // Iniciar cuando el DOM esté listo
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
  
})();
