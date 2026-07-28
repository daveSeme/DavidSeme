import { Redis } from '@upstash/redis';

export const config = { runtime: 'edge' };

const redis = new Redis({
  url: process.env.UPSTASH_REDIS_REST_URL,
  token: process.env.UPSTASH_REDIS_REST_TOKEN,
});

export default async function handler(req) {
  const url = new URL(req.url);
  const password = url.searchParams.get('password');
  const ADMIN_PASSWORD = 'Seme2026!Secure';
  
  if (password !== ADMIN_PASSWORD) {
    return new Response(JSON.stringify({ error: 'Unauthorized' }), {
      status: 401,
      headers: { 'Content-Type': 'application/json' }
    });
  }

  try {
    const visitorIds = await redis.zrange('visitors:index', 0, 99, { rev: true });
    const visitors = [];
    for (const id of visitorIds) {
      const data = await redis.hgetall(id);
      if (data) visitors.push({ id, ...data });
    }
    
    const requestIds = await redis.zrange('requests:index', 0, 49, { rev: true });
    const requests = [];
    for (const id of requestIds) {
      const data = await redis.hgetall(id);
      if (data) requests.push({ id, ...data });
    }
    
    const dailyCounts = await redis.hgetall('daily:count') || {};
    const pageCounts = await redis.hgetall('pages:count') || {};
    const today = new Date().toISOString().split('T')[0];

    return new Response(JSON.stringify({
      stats: {
        totalVisitors: await redis.zcard('visitors:index'),
        todayVisitors: parseInt(dailyCounts[today]) || 0,
        uniqueIPs: new Set(visitors.map(v => v.ip)).size,
        totalRequests: await redis.zcard('requests:index'),
        pendingRequests: requests.filter(r => r.status === 'pending').length
      },
      visitors: visitors.slice(0, 20),
      requests,
      pageCounts
    }), {
      status: 200,
      headers: { 'Content-Type': 'application/json' }
    });
  } catch (error) {
    return new Response(JSON.stringify({ error: error.message }), {
      status: 500,
      headers: { 'Content-Type': 'application/json' }
    });
  }
}