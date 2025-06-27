// Initialize Three.js Particles
document.addEventListener('DOMContentLoaded', () => {
  const scene = new THREE.Scene();
  const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
  const renderer = new THREE.WebGLRenderer({ alpha: true });
  renderer.setSize(window.innerWidth, window.innerHeight);
  document.getElementById('particles-js').appendChild(renderer.domElement);

  // Create particles
  const particles = new THREE.BufferGeometry();
  const particleCount = 500;
  const posArray = new Float32Array(particleCount * 3);

  for(let i = 0; i < particleCount * 3; i++) {
    posArray[i] = (Math.random() - 0.5) * 5;
  }

  particles.setAttribute('position', new THREE.BufferAttribute(posArray, 3));
  const particleMaterial = new THREE.PointsMaterial({
    size: 0.02,
    color: 0x00a8ff,
    transparent: true,
    opacity: 0.8
  });

  const particleMesh = new THREE.Points(particles, particleMaterial);
  scene.add(particleMesh);
  camera.position.z = 2;

  // Animation
  function animate() {
    requestAnimationFrame(animate);
    particleMesh.rotation.x += 0.001;
    particleMesh.rotation.y += 0.001;
    renderer.render(scene, camera);
  }
  animate();

  // Handle window resize
  window.addEventListener('resize', () => {
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
  });
});