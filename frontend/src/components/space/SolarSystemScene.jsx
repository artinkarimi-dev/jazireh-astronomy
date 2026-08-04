import { Canvas, useFrame } from '@react-three/fiber'
import { OrbitControls, Stars, Html } from '@react-three/drei'
import { Suspense, useRef } from 'react'
import * as THREE from 'three'

function Planet({ planet, onSelect, selected }) {
  const orbitRef = useRef()
  const meshRef = useRef()
  useFrame((_, delta) => {
    if (orbitRef.current) orbitRef.current.rotation.y += delta * planet.speed
    if (meshRef.current) meshRef.current.rotation.y += delta * .15
  })
  const position = [planet.distance, 0, 0]
  return (
    <group ref={orbitRef}>
      <mesh rotation={[-Math.PI / 2, 0, 0]}>
        <ringGeometry args={[planet.distance - .015, planet.distance + .015, 128]} />
        <meshBasicMaterial color="#27415f" transparent opacity={.5} side={THREE.DoubleSide} />
      </mesh>
      <group position={position}>
        <mesh ref={meshRef} onClick={(e) => { e.stopPropagation(); onSelect(planet) }} scale={selected ? 1.13 : 1}>
          <sphereGeometry args={[planet.size, 48, 48]} />
          <meshStandardMaterial color={planet.color} roughness={.72} metalness={.04} emissive={planet.color} emissiveIntensity={selected ? .12 : .03} />
        </mesh>
        {planet.ring && <mesh rotation={[Math.PI / 2.4, 0, 0]}><ringGeometry args={[planet.size * 1.35, planet.size * 2, 64]} /><meshStandardMaterial color="#bfa679" transparent opacity={.72} side={THREE.DoubleSide} /></mesh>}
        {selected && <Html center distanceFactor={13}><div className="whitespace-nowrap rounded-full border border-blue-300/30 bg-space-950/90 px-3 py-1 text-xs font-bold text-blue-100 backdrop-blur">{planet.name}</div></Html>}
      </group>
    </group>
  )
}

export default function SolarSystemScene({ planets, selected, onSelect }) {
  return (
    <Canvas camera={{ position: [0, 17, 31], fov: 52 }} dpr={[1, 1.7]} gl={{ antialias: true, powerPreference: 'high-performance' }}>
      <color attach="background" args={['#010711']} />
      <ambientLight intensity={.45} />
      <pointLight position={[0, 0, 0]} intensity={180} distance={90} color="#fff2d0" />
      <Suspense fallback={null}>
        <Stars radius={85} depth={55} count={3400} factor={3.2} saturation={0} fade speed={.25} />
        <mesh onClick={() => onSelect(null)}>
          <sphereGeometry args={[2.25, 64, 64]} />
          <meshBasicMaterial color="#ffb347" />
        </mesh>
        <pointLight position={[0, 0, 0]} intensity={250} distance={80} color="#ffd18a" />
        {planets.map((planet) => <Planet key={planet.id} planet={planet} onSelect={onSelect} selected={selected?.id === planet.id} />)}
      </Suspense>
      <OrbitControls enableDamping dampingFactor={.06} minDistance={12} maxDistance={55} maxPolarAngle={Math.PI / 2.05} />
    </Canvas>
  )
}
