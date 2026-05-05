self.addEventListener("push", function(event) {
    const options = {
        body: "A live começou! Clique para assistir 🔴",
        icon: "icon.png", // Ícone da notificação
        badge: "badge.png",
        vibrate: [200, 100, 200],
        data: { url: "https://seusite.com/live" }, // URL da live
    };

    event.waitUntil(
        self.registration.showNotification("🔴 Live ao Vivo!", options)
    );
});

self.addEventListener("notificationclick", function(event) {
    event.notification.close();
    event.waitUntil(
        clients.openWindow(event.notification.data.url)
    );
});
