
</head>
<body>
    <div id="container">
        <div id="videoContainer">
            <video id="video" autoplay></video>
            <button id="snap" class="nut">Chụp ảnh</button>
        </div>
        <div class="captured-image">
            <div id="capturedImageContainer"></div>
            <button id="compareButton" class="nut" style="display:none;">Chấm công</button>
        </div>
    </div>
    <div id="map"></div>
    <script>
        // Khởi tạo bản đồ Goong
        goongjs.accessToken = 'EBn0P3DAihTj0YkWXwc6CAqP5C5ScF7tNOwE6LfG';
        let map = new goongjs.Map({
            container: 'map',
            style: 'https://tiles.goong.io/assets/goong_map_web.json',
            center: [106.7116815, 10.821203], // Vị trí mặc định của bản đồ
            zoom: 15
        });

        let targetLocation = { lat: null, lng: null };
        const checkRadius = 200; // Bán kính kiểm tra, tính bằng mét

        async function geocodeAddress(address) {
            const apiKey = 'MiyG7vGc63MqSJRZ1uhGkzDRiJdZdvdGIhQCTasy';
            const url = `https://rsapi.goong.io/Geocode?address=${encodeURIComponent(address)}&api_key=${apiKey}`;

            try {
                const response = await fetch(url);
                const data = await response.json();
                if (data.results && data.results.length > 0) {
                    const location = data.results[0].geometry.location;
                    targetLocation = { lat: location.lat, lng: location.lng };
                    console.log(`Địa chỉ được chuyển đổi thành tọa độ: ${targetLocation.lat}, ${targetLocation.lng}`);
                    
                    // Cập nhật bản đồ với điểm mốc mới
                    map.setCenter([targetLocation.lng, targetLocation.lat]);
                    addRedMarker(targetLocation);

                    // Khi có targetLocation, gọi updatePosition
                    getLocation();
                } else {
                    console.error('Không tìm thấy kết quả');
                }
            } catch (error) {
                console.error('Lỗi khi geocode địa chỉ:', error);
            }
        }

        async function getDistance(lat1, lon1, lat2, lon2) { 
            console.log(`Lat1: ${lat1}, Lon1: ${lon1}`);
            console.log(`Lat2: ${lat2}, Lon2: ${lon2}`);
            const R = 6371e3; // Earth's radius in meters
            const φ1  = lat1 * Math.PI / 180;// Chuyển đổi vĩ độ 1 sang radian
            const φ2 = lat2 * Math.PI / 180; 
            const Δφ = (lat2 - lat1) * Math.PI / 180;// Chênh lệch vĩ độ
            const Δλ = (lon2 - lon1) * Math.PI / 180;// Chênh lệch kinh độ

            const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
                Math.cos(φ1) * Math.cos(φ2) *
                Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
        
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

            return R * c; //  Khoảng cách theo mét
            //Công thức Haversine (trên bề mặt trái đất)
        }
        console.log(targetLocation.lat);
        console.log(targetLocation.lng);
        let currentPosition = { latitude: null, longitude: null };
        async function updatePosition(position) {
            const { latitude, longitude } = position.coords;
            console.log(`Vĩ độ: ${latitude}, Kinh độ: ${longitude}`);
            currentPosition.latitude = latitude;
            currentPosition.longitude = longitude;
            // Cập nhật vị trí hiện tại trên bản đồ
            map.setCenter([longitude, latitude]);

            // Thêm marker cho vị trí hiện tại (màu xanh)
            new goongjs.Marker({ color: 'blue' })
                .setLngLat([longitude, latitude])
                .setPopup(new goongjs.Popup().setHTML('<div style="color: blue;">Vị trí hiện tại</div>'))
                .addTo(map);
            
            // Xóa lớp vòng tròn cũ nếu có
            if (map.getSource('circleSource')) {
                map.removeLayer('circleLayer');
                map.removeSource('circleSource');
            }

            // Thêm vòng tròn mới vào bản đồ
            addCircleLayer();
       
            // Thêm điểm mốc màu đỏ
            addRedMarker(targetLocation);
            console.log(targetLocation.lat);
            console.log(targetLocation.lng);
            // Kiểm tra nếu điểm mốc nằm trong vùng vòng tròn xanh
            const distance = await getDistance(currentPosition.latitude, currentPosition.longitude, targetLocation.lat, targetLocation.lng);
            console.log(`Khoảng cách giữa vị trí hiện tại và điểm mốc: ${distance} mét`);
            if (distance <= checkRadius) {
                setButtonsState(false); // Cho phép bấm nút Chấm công
            } else {
                setButtonsState(true); // Không cho phép bấm nút Chấm công
            }
        }

        async function addCircleLayer() {
            // Tạo dữ liệu GeoJSON cho vòng tròn bằng turf.js
            const circle = turf.circle([currentPosition.longitude,currentPosition.latitude], checkRadius , {
                steps: 64,
               units: 'meters'
            });
            map.addSource('circleSource', {
                type: 'geojson',
                data: circle
            });
            // Thêm lớp vòng tròn vào bản đồ
            map.addLayer({
                id: 'circleLayer',
                type: 'fill',
                source: 'circleSource',
                paint: {
                    'fill-color': 'rgba(0, 0, 255, 0.2)', // Màu nội dung vòng tròn
                    'fill-outline-color': 'rgba(0, 0, 255, 0.5)' // Màu viền vòng tròn
                }
            });
        }

        function addRedMarker(location) {
            new goongjs.Marker({ color: 'red' })
                .setLngLat([location.lng, location.lat])
                .setPopup(new goongjs.Popup().setHTML('<div style="color: red;">Điểm mốc</div>'))
                .addTo(map);
        }

        function handleLocationError(error) {
            console.error('Error getting location: ', error);
        }

        function getLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(updatePosition, handleLocationError, {
                    enableHighAccuracy: true, // Sử dụng độ chính xác cao hơn
                    timeout: 5000, // Thời gian timeout
                    maximumAge: 0 // Không sử dụng vị trí cũ
                });
            } else {
                console.error('Geolocation is not supported by this browser.');
            }
        }

        const video = document.getElementById('video');
        const snapButton = document.getElementById('snap');
        const capturedImageContainer = document.getElementById('capturedImageContainer');
        const compareButton = document.getElementById('compareButton');

        let capturedImage = null;

        async function initCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                video.srcObject = stream;
            } catch (err) {
                console.error('Error accessing the camera: ', err);
            }
        }

        async function takeSnapshot() {
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const context = canvas.getContext('2d');
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            capturedImage = canvas.toDataURL('image/png');
            capturedImageContainer.innerHTML = `<img src="${capturedImage}" alt="Captured Image">`;
            compareButton.style.display = 'block';
        }

        function displayCapturedImage(imageData) {
            capturedImageContainer.innerHTML = '';
            const img = document.createElement('img');
            img.src = imageData;
            img.alt = `Captured Image`;
            capturedImageContainer.appendChild(img);
        }

        function setButtonsState(disabled) {
            snapButton.disabled = disabled;
            compareButton.disabled = disabled;
            if (disabled) {
                snapButton.classList.add('disabled');
                compareButton.classList.add('disabled');
            } else {
                snapButton.classList.remove('disabled');
                compareButton.classList.remove('disabled');
            }
        }

        snapButton.addEventListener('click', takeSnapshot);

        compareButton.addEventListener('click', async () => {
            compareButton.textContent = 'Vui lòng chờ...';
            setButtonsState(true);
            if (capturedImage) {
                await compareWithTrainedData(capturedImage);
            } else {
                console.error('Please capture an image first.');
                setButtonsState(false);
                compareButton.textContent = 'Chấm công';
            }
        });

        async function initFaceAPI() {
            await faceapi.nets.ssdMobilenetv1.loadFromUri('./models');
            await faceapi.nets.faceLandmark68Net.loadFromUri('./models');
            await faceapi.nets.faceRecognitionNet.loadFromUri('./models');
        }

        async function compareWithTrainedData(imageData) {
            await initFaceAPI();
            const response = await fetch('get_training_data.php');
            const trainingData = await response.json();

            const faceDescriptors = [];
            trainingData.forEach(({ label, descriptors }) => {
                const float32ArrayDescriptors = descriptors.map(descriptor => new Float32Array(descriptor));
                faceDescriptors.push(new faceapi.LabeledFaceDescriptors(label, float32ArrayDescriptors));
            });

            const faceMatcher = new faceapi.FaceMatcher(faceDescriptors, 0.5);
            const image = await getImageFromDataUrl(imageData);
            const detections = await faceapi.detectAllFaces(image).withFaceLandmarks().withFaceDescriptors();

            if (detections.length > 0) {
                const resizedDetections = faceapi.resizeResults(detections, image);
                for (const detection of resizedDetections) {
                    const bestMatch = faceMatcher.findBestMatch(detection.descriptor);
                    if (bestMatch.label !== 'unknown') {
                        console.log(`Kết quả: Nhận diện khuôn mặt của ${bestMatch.label} với độ chính xác ${bestMatch.distance}`);
                        await addToAttendance(bestMatch.label);
                        Swal.fire({
                        icon: 'success',
                        title: 'Chấm công thành công',
                        confirmButtonText: 'OK'
                        });
                        setButtonsState(false);
                        compareButton.textContent = 'Chấm công';
                        return;
                    }
                }
                console.log('Khuôn mặt không khớp với dữ liệu đã lưu');
            } else {
                console.log('Không tìm thấy khuôn mặt trong hình ảnh');
            }
            Swal.fire({
            icon: 'error',
            title: 'Chấm công không thành công',
            text: 'Khuôn mặt không khớp hoặc không tìm thấy khuôn mặt',
            confirmButtonText: 'OK'
            });
            setButtonsState(false);
            compareButton.textContent = 'Chấm công';
        }
        async function getImageFromDataUrl(dataUrl) {
            return new Promise((resolve) => {
                const img = new Image();
                img.onload = () => resolve(img);
                img.src = dataUrl;
            });
        }

        async function addToAttendance(label) {
            try {
                const currentDate = new Date();
                const currentTime = currentDate.getHours() + ':' + currentDate.getMinutes() + ':' + currentDate.getSeconds();
                const currentDateFormatted = currentDate.getFullYear() + '-' + (currentDate.getMonth() + 1) + '-' + currentDate.getDate();
                const vido = currentPosition.latitude;
                const kinhdo = currentPosition.longitude;
                console.log(vido);
                const response = await fetch('insertgiolam.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        label: label,
                        time: currentTime,
                        date: currentDateFormatted,
                        vido: vido,
                        kinhdo: kinhdo
                    })
                });
                console.log(response);
                if (response.ok) {
                    console.log('Dữ liệu đã được thêm vào bảng chấm công.');
                } else {
                    console.error('Lỗi khi thêm dữ liệu vào bảng chấm công.');
                }
            } catch (error) {
                console.error('Lỗi:', error);
            }
        }
        window.addEventListener('DOMContentLoaded', () => {
                    initFaceAPI();
                });
                
        // Khởi động camera khi trang được tải
        initCamera();

        // Gọi hàm geocodeAddress khi trang được tải
        geocodeAddress('520 D. Thị Mười, Hiệp Thành, Quận 12, Hồ Chí Minh, Việt Nam');
        // geocodeAddress('114 trần Bình trọng p1 quận gò vấp');

        function setButtonsState(disabled) {
            compareButton.disabled = disabled;
            compareButton.classList.toggle('disabled', disabled);
        }
    </script>
</body>
</html>
